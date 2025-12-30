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
        'stage',
        'question_id',
        'approved',
        'comment',
        'reviewer_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get all question reviews for a project supplier at specific stage
     */
    public function getReviewsForProjectSupplier(int $projectSupplierId, int $stage = null): array
    {
        $builder = $this->builder()
            ->select('question_reviews.*, users.username as reviewer_name')
            ->join('users', 'users.id = question_reviews.reviewer_id', 'left')
            ->where('question_reviews.project_supplier_id', $projectSupplierId);

        if ($stage !== null) {
            $builder->where('question_reviews.stage', $stage);
        }

        $reviews = $builder->get()->getResult(QuestionReview::class);

        $result = [];
        foreach ($reviews as $review) {
            $result[$review->question_id] = $review->toApiResponse();
        }

        return $result;
    }

    /**
     * Save or update question reviews (batch upsert) for a specific stage
     */
    public function saveReviews(int $projectSupplierId, array $reviews, int $reviewerId, int $stage = 1): int
    {
        $count = 0;

        foreach ($reviews as $questionId => $reviewData) {
            $existing = $this->where('project_supplier_id', $projectSupplierId)
                ->where('stage', $stage)
                ->where('question_id', $questionId)
                ->first();

            $data = [
                'project_supplier_id' => $projectSupplierId,
                'stage' => $stage,
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
     * @param array $projectSupplierIds Array of supplier IDs
     * @param array $stagesMap Optional map of project_supplier_id => current_stage  
     */
    public function getReviewedCountsForProjectSuppliers(array $projectSupplierIds, array $stagesMap = []): array
    {
        if (empty($projectSupplierIds)) {
            return [];
        }

        // Ensure all IDs are integers
        $projectSupplierIds = array_values(array_map('intval', $projectSupplierIds));

        $counts = [];

        // If we have stages map, query per supplier's current stage
        if (!empty($stagesMap)) {
            foreach ($projectSupplierIds as $psId) {
                $stage = $stagesMap[$psId] ?? 1;

                $result = $this->builder()
                    ->select('COUNT(*) as total_reviewed')
                    ->select('SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) as approved_count')
                    ->select('SUM(CASE WHEN approved = 0 THEN 1 ELSE 0 END) as rejected_count')
                    ->where('project_supplier_id', $psId)
                    ->where('stage', $stage)
                    ->get()
                    ->getRowArray();

                $counts[$psId] = [
                    'total' => (int)($result['total_reviewed'] ?? 0),
                    'approved' => (int)($result['approved_count'] ?? 0),
                    'rejected' => (int)($result['rejected_count'] ?? 0)
                ];
            }
        } else {
            // No stage filter - count all (backward compatibility)
            $results = $this->builder()
                ->select('project_supplier_id')
                ->select('COUNT(*) as total_reviewed')
                ->select('SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) as approved_count')
                ->select('SUM(CASE WHEN approved = 0 THEN 1 ELSE 0 END) as rejected_count')
                ->whereIn('project_supplier_id', $projectSupplierIds)
                ->groupBy('project_supplier_id')
                ->get()
                ->getResultArray();

            foreach ($results as $row) {
                $counts[$row['project_supplier_id']] = [
                    'total' => (int)$row['total_reviewed'],
                    'approved' => (int)$row['approved_count'],
                    'rejected' => (int)$row['rejected_count']
                ];
            }
        }

        return $counts;
    }
}
