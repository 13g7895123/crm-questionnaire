<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\QuestionReview;

class QuestionReviewModel extends Model
{
    protected $table = 'question_reviews';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = QuestionReview::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'project_supplier_id',
        'question_id',
        'approved',
        'comment',
        'reviewer_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get all question reviews for a project supplier
     */
    public function getReviewsForProjectSupplier(int $projectSupplierId): array
    {
        $reviews = $this->builder()
            ->select('question_reviews.*, users.username as reviewer_name')
            ->join('users', 'users.id = question_reviews.reviewer_id', 'left')
            ->where('question_reviews.project_supplier_id', $projectSupplierId)
            ->get()
            ->getResult(QuestionReview::class);

        $result = [];
        foreach ($reviews as $review) {
            $result[$review->question_id] = $review->toApiResponse();
        }

        return $result;
    }

    /**
     * Save or update question reviews (batch upsert)
     */
    public function saveReviews(int $projectSupplierId, array $reviews, int $reviewerId): int
    {
        $count = 0;

        foreach ($reviews as $questionId => $reviewData) {
            $existing = $this->where('project_supplier_id', $projectSupplierId)
                ->where('question_id', $questionId)
                ->first();

            $data = [
                'project_supplier_id' => $projectSupplierId,
                'question_id' => $questionId,
                'approved' => $reviewData['approved'] ? 1 : 0,
                'comment' => $reviewData['comment'] ?? '',
                'reviewer_id' => $reviewerId,
            ];

            if ($existing) {
                $this->update($existing->id, $data);
            } else {
                $this->insert($data);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Delete all reviews for a project supplier
     */
    public function deleteByProjectSupplier(int $projectSupplierId): bool
    {
        return $this->where('project_supplier_id', $projectSupplierId)->delete();
    }

    /**
     * Get reviewed question counts for multiple project suppliers
     * Returns array keyed by project_supplier_id with breakdown of counts
     */
    public function getReviewedCountsForProjectSuppliers(array $projectSupplierIds): array
    {
        if (empty($projectSupplierIds)) {
            return [];
        }

        $results = $this->builder()
            ->select('project_supplier_id')
            ->select('COUNT(*) as total_reviewed')
            ->select('SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) as approved_count')
            ->select('SUM(CASE WHEN approved = 0 THEN 1 ELSE 0 END) as rejected_count')
            ->whereIn('project_supplier_id', $projectSupplierIds)
            ->groupBy('project_supplier_id')
            ->get()
            ->getResultArray();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['project_supplier_id']] = [
                'total' => (int)$row['total_reviewed'],
                'approved' => (int)$row['approved_count'],
                'rejected' => (int)$row['rejected_count']
            ];
        }

        return $counts;
    }
}
