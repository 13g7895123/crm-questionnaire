<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\TemplateSection;

class TemplateSectionModel extends Model
{
    protected $table = 'template_sections';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = TemplateSection::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'template_id',
        'section_id',
        'order',
        'title',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'template_id' => 'required|is_natural_no_zero',
        'section_id' => 'required|max_length[10]',
        'order' => 'required|is_natural_no_zero',
        'title' => 'required|max_length[200]',
    ];

    /**
     * Get sections by template ID with subsections
     */
    public function getSectionsByTemplateId(int $templateId): array
    {
        $sections = $this->where('template_id', $templateId)
                        ->orderBy('order', 'ASC')
                        ->findAll();

        $subsectionModel = model('TemplateSubsectionModel');
        
        foreach ($sections as &$section) {
            $section->subsections = $subsectionModel->getSubsectionsBySectionId($section->id);
        }

        return $sections;
    }

    /**
     * Delete sections by template ID (cascade will handle subsections and questions)
     */
    public function deleteSectionsByTemplateId(int $templateId): bool
    {
        return $this->where('template_id', $templateId)->delete();
    }
}
