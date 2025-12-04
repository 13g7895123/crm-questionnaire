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
                'value' => $answer->value,
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

            $value = is_array($answerData['value']) || is_object($answerData['value']) 
                ? json_encode($answerData['value']) 
                : $answerData['value'];

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

        return $answer?->updated_at?->toIso8601String();
    }

    /**
     * @deprecated Use getAnswersForProjectSupplier instead
     */
    public function getAnswersForProject(int $projectId): array
    {
        return $this->getAnswersForProjectSupplier($projectId);
    }
}
