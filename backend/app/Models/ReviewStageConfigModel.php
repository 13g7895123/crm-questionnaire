<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\ReviewStageConfig;

class ReviewStageConfigModel extends Model
{
    protected $table = 'review_stage_configs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = ReviewStageConfig::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id',
        'project_id',
        'stage_order',
        'department_id',
        'approver_id',
    ];

    protected $useTimestamps = false;

    /**
     * Get review config for a project with department info
     */
    public function getConfigForProject(string $projectId): array
    {
        return $this->builder()
            ->select('review_stage_configs.*, departments.name as department_name')
            ->join('departments', 'departments.id = review_stage_configs.department_id', 'left')
            ->where('review_stage_configs.project_id', $projectId)
            ->orderBy('review_stage_configs.stage_order', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Create review config for project
     */
    public function createConfigForProject(string $projectId, array $reviewConfig): bool
    {
        // Delete existing config
        $this->where('project_id', $projectId)->delete();

        // Insert new config
        foreach ($reviewConfig as $config) {
            $this->insert([
                'id' => $this->generateUuid(),
                'project_id' => $projectId,
                'stage_order' => $config['stageOrder'],
                'department_id' => $config['departmentId'],
                'approver_id' => $config['approverId'] ?? null,
            ]);
        }

        return true;
    }

    /**
     * Get stage by project and stage order
     */
    public function getStage(string $projectId, int $stageOrder): ?ReviewStageConfig
    {
        return $this->where('project_id', $projectId)
                    ->where('stage_order', $stageOrder)
                    ->first();
    }

    /**
     * Get total stages for a project
     */
    public function getTotalStages(string $projectId): int
    {
        return $this->where('project_id', $projectId)->countAllResults();
    }

    /**
     * Generate UUID
     */
    protected function generateUuid(): string
    {
        return sprintf('%s_%s',
            'rsc',
            bin2hex(random_bytes(12))
        );
    }
}
