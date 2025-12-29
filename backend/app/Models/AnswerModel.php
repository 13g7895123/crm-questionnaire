<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Answer;

class AnswerModel extends Model
{
    protected $table = 'answers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = Answer::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'project_supplier_id',
        'question_id',
        'value',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get answers for a project supplier
     */
    public function getAnswersForProjectSupplier(int $projectSupplierId): array
    {
        $answers = $this->where('project_supplier_id', $projectSupplierId)->findAll();

        $result = [];
        foreach ($answers as $answer) {
            $result[$answer->question_id] = [
                'questionId' => $answer->question_id,
                'value' => $answer->getValue(),
            ];
        }

        return $result;
    }

    /**
     * Save or update answers for a project supplier
     */
    public function saveAnswers(int $projectSupplierId, array $answers): int
    {
        $count = 0;
        foreach ($answers as $questionId => $answerData) {
            $existing = $this->where('project_supplier_id', $projectSupplierId)
                ->where('question_id', $questionId)
                ->first();

            // Get the raw value
            $rawValue = $answerData['value'] ?? null;

            // Always JSON encode for MySQL JSON column type
            // This handles all types: null, bool, string, number, array, object
            $value = json_encode($rawValue, JSON_UNESCAPED_UNICODE);

            if ($existing) {
                $this->update($existing->id, ['value' => $value]);
            } else {
                $this->insert([
                    'project_supplier_id' => $projectSupplierId,
                    'question_id' => $questionId,
                    'value' => $value,
                ]);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Get last saved timestamp for project supplier
     */
    public function getLastSavedAt(int $projectSupplierId): ?string
    {
        $answer = $this->where('project_supplier_id', $projectSupplierId)
            ->orderBy('updated_at', 'DESC')
            ->first();

        if (!$answer || !$answer->updated_at) {
            return null;
        }

        // Use format('c') for ISO 8601 format
        return $answer->updated_at->format('c');
    }

    /**
     * @deprecated Use getAnswersForProjectSupplier instead
     */
    public function getAnswersForProject(int $projectId): array
    {
        return $this->getAnswersForProjectSupplier($projectId);
    }
}
