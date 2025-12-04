<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Template;

class TemplateModel extends Model
{
    protected $table = 'templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = Template::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name',
        'type',
        'latest_version',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|max_length[200]',
        'type' => 'required|in_list[SAQ,CONFLICT]',
    ];

    /**
     * Get templates with versions
     */
    public function getTemplatesWithVersions(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->where('templates.deleted_at IS NULL');

        if (!empty($filters['type'])) {
            $builder->where('templates.type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $builder->like('templates.name', $filters['search']);
        }

        $total = $builder->countAllResults(false);
        $templates = $builder->limit($limit, ($page - 1) * $limit)->get()->getResult();

        // Get versions for each template
        $versionModel = model('TemplateVersionModel');
        foreach ($templates as &$template) {
            $template->versions = $versionModel->where('template_id', $template->id)
                                               ->orderBy('version', 'DESC')
                                               ->findAll();
        }

        return [
            'data' => $templates,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ];
    }

    /**
     * Check if template is in use by any project
     */
    public function isInUse(string $id): int
    {
        return model('ProjectModel')
            ->where('template_id', $id)
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }
}
