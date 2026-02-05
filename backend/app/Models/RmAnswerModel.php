<?php

namespace App\Models;

use CodeIgniter\Model;

class RmAnswerModel extends Model
{
    protected $table = 'rm_answers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'assignment_id',
        'template_type',
        'company_name',
        'company_country',
        'company_address',
        'company_contact_name',
        'company_contact_email',
        'company_contact_phone',
        'authorizer',
        'effective_date',
        'declaration_scope',
        'mineral_declaration',
        'policy_answers',
        'excel_file_path',
        'validation_warnings',
        'submitted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Callbacks to handle JSON
    protected $afterFind = ['processJsonFields'];

    protected function processJsonFields(array $data)
    {
        if (!isset($data['data'])) return $data;

        $jsonFields = ['mineral_declaration', 'policy_answers', 'validation_warnings'];

        if (isset($data['data']['assignment_id'])) {
            // Single result
            foreach ($jsonFields as $field) {
                if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                    $data['data'][$field] = json_decode($data['data'][$field], true);
                }
            }
        } else {
            // Multiple results
            foreach ($data['data'] as &$row) {
                foreach ($jsonFields as $field) {
                    if (isset($row[$field]) && is_string($row[$field])) {
                        $row[$field] = json_decode($row[$field], true);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 取得指派的所有回答
     */
    public function getAnswersByAssignment(int $assignmentId)
    {
        return $this->where('assignment_id', $assignmentId)->findAll();
    }
}
