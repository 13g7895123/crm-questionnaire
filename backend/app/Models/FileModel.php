<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\File;

class FileModel extends Model
{
    protected $table = 'files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = File::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id',
        'project_id',
        'question_id',
        'file_name',
        'file_path',
        'file_url',
        'file_size',
        'content_type',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false;

    /**
     * Create a file record
     */
    public function createFile(array $data): File
    {
        $data['id'] = $this->generateUuid();
        $this->insert($data);

        return $this->find($data['id']);
    }

    /**
     * Generate UUID
     */
    protected function generateUuid(): string
    {
        return sprintf('%s_%s',
            'file',
            bin2hex(random_bytes(12))
        );
    }
}
