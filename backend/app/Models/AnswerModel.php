<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Answer;

class AnswerModel extends Model
{
    protected $table = 'answers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = Answer::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id',
        'project_id',
        'question_id',
        'value',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get answers for a project
     */
    public function getAnswersForProject(string $projectId): array
    {
        $answers = $this->where('project_id', $projectId)->findAll();
        
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
     * Save or update answers for a project
     */
    public function saveAnswers(string $projectId, array $answers): int
    {
        $count = 0;
        foreach ($answers as $questionId => $answerData) {
            $existing = $this->where('project_id', $projectId)
                            ->where('question_id', $questionId)
                            ->first();

            $value = is_array($answerData['value']) || is_object($answerData['value']) 
                ? json_encode($answerData['value']) 
                : $answerData['value'];

            if ($existing) {
                $this->update($existing->id, ['value' => $value]);
            } else {
                $this->insert([
                    'id' => $this->generateUuid(),
                    'project_id' => $projectId,
                    'question_id' => $questionId,
                    'value' => $value,
                ]);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Get last saved timestamp for project
     */
    public function getLastSavedAt(string $projectId): ?string
    {
        $answer = $this->where('project_id', $projectId)
                       ->orderBy('updated_at', 'DESC')
                       ->first();

        return $answer?->updated_at?->toIso8601String();
    }

    /**
     * Generate UUID
     */
    protected function generateUuid(): string
    {
        return sprintf('%s_%s',
            'ans',
            bin2hex(random_bytes(12))
        );
    }
}
