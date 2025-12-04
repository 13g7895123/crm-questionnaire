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
    public function getAnswersForProject(int $projectId): array
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
    public function saveAnswers(int $projectId, array $answers): int
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
    public function getLastSavedAt(int $projectId): ?string
    {
        $answer = $this->where('project_id', $projectId)
                       ->orderBy('updated_at', 'DESC')
                       ->first();

        return $answer?->updated_at?->toIso8601String();
    }
}
