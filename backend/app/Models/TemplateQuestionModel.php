<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\TemplateQuestion;

class TemplateQuestionModel extends Model
{
    protected $table = 'template_questions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = TemplateQuestion::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'subsection_id',
        'question_id',
        'order',
        'text',
        'type',
        'required',
        'config',
        'conditional_logic',
        'table_config',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'subsection_id' => 'required|is_natural_no_zero',
        'question_id' => 'required|max_length[50]',
        'order' => 'required|is_natural_no_zero',
        'text' => 'required',
        'type' => 'required|in_list[BOOLEAN,TEXT,NUMBER,RADIO,CHECKBOX,SELECT,DATE,FILE,TABLE]',
        'required' => 'required|in_list[0,1]',
    ];

    /**
     * Get questions by subsection ID
     */
    public function getQuestionsBySubsectionId(int $subsectionId): array
    {
        return $this->where('subsection_id', $subsectionId)
                   ->orderBy('order', 'ASC')
                   ->findAll();
    }

    /**
     * Delete questions by subsection ID
     */
    public function deleteQuestionsBySubsectionId(int $subsectionId): bool
    {
        return $this->where('subsection_id', $subsectionId)->delete();
    }

    /**
     * Get all questions for a template (for validation/scoring)
     */
    public function getQuestionsByTemplateId(int $templateId): array
    {
        $builder = $this->builder();
        
        return $builder->select('template_questions.*')
                      ->join('template_subsections', 'template_subsections.id = template_questions.subsection_id')
                      ->join('template_sections', 'template_sections.id = template_subsections.section_id')
                      ->where('template_sections.template_id', $templateId)
                      ->orderBy('template_sections.order, template_subsections.order, template_questions.order')
                      ->get()
                      ->getResult(TemplateQuestion::class);
    }
}
