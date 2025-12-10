<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\TemplateSubsection;

class TemplateSubsectionModel extends Model
{
    protected $table = 'template_subsections';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = TemplateSubsection::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'section_id',
        'subsection_id',
        'order',
        'title',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'section_id' => 'required|is_natural_no_zero',
        'subsection_id' => 'required|max_length[20]',
        'order' => 'required|is_natural_no_zero',
        'title' => 'required|max_length[200]',
    ];

    /**
     * Get subsections by section ID with questions
     */
    public function getSubsectionsBySectionId(int $sectionId): array
    {
        $subsections = $this->where('section_id', $sectionId)
                           ->orderBy('order', 'ASC')
                           ->findAll();

        $questionModel = model('TemplateQuestionModel');
        
        foreach ($subsections as &$subsection) {
            $subsection->questions = $questionModel->getQuestionsBySubsectionId($subsection->id);
        }

        return $subsections;
    }

    /**
     * Delete subsections by section ID (cascade will handle questions)
     */
    public function deleteSubsectionsBySectionId(int $sectionId): bool
    {
        return $this->where('section_id', $sectionId)->delete();
    }
}
