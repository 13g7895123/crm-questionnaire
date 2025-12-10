<?php

namespace App\Repositories;

use App\Models\TemplateSectionModel;
use App\Models\TemplateSubsectionModel;
use App\Models\TemplateQuestionModel;

class TemplateStructureRepository
{
    protected TemplateSectionModel $sectionModel;
    protected TemplateSubsectionModel $subsectionModel;
    protected TemplateQuestionModel $questionModel;

    public function __construct()
    {
        $this->sectionModel = new TemplateSectionModel();
        $this->subsectionModel = new TemplateSubsectionModel();
        $this->questionModel = new TemplateQuestionModel();
    }

    /**
     * Get complete template structure with sections, subsections, and questions
     */
    public function getTemplateStructure(int $templateId): array
    {
        $sections = $this->sectionModel->where('template_id', $templateId)
                                      ->orderBy('order', 'ASC')
                                      ->findAll();

        $structure = [];
        
        foreach ($sections as $section) {
            $subsections = $this->subsectionModel->where('section_id', $section->id)
                                                ->orderBy('order', 'ASC')
                                                ->findAll();

            $subsectionsData = [];
            
            foreach ($subsections as $subsection) {
                $questions = $this->questionModel->where('subsection_id', $subsection->id)
                                                ->orderBy('order', 'ASC')
                                                ->findAll();

                $questionsData = array_map(function ($question) {
                    return $question->toApiResponse();
                }, $questions);

                $subsectionsData[] = array_merge(
                    $subsection->toApiResponse(),
                    ['questions' => $questionsData]
                );
            }

            $structure[] = array_merge(
                $section->toApiResponse(),
                ['subsections' => $subsectionsData]
            );
        }

        return $structure;
    }

    /**
     * Save complete template structure (sections + subsections + questions)
     * 
     * @param int $templateId
     * @param array $sections Array of section data with nested subsections and questions
     * @return bool
     */
    public function saveTemplateStructure(int $templateId, array $sections): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete existing structure
            $this->sectionModel->deleteSectionsByTemplateId($templateId);

            // Insert new structure
            foreach ($sections as $sectionData) {
                $sectionId = $this->sectionModel->insert([
                    'template_id' => $templateId,
                    'section_id' => $sectionData['id'],
                    'order' => $sectionData['order'],
                    'title' => $sectionData['title'],
                    'description' => $sectionData['description'] ?? null,
                ]);

                if (!$sectionId) {
                    throw new \RuntimeException('Failed to insert section');
                }

                foreach ($sectionData['subsections'] ?? [] as $subsectionData) {
                    $subsectionId = $this->subsectionModel->insert([
                        'section_id' => $sectionId,
                        'subsection_id' => $subsectionData['id'],
                        'order' => $subsectionData['order'],
                        'title' => $subsectionData['title'],
                        'description' => $subsectionData['description'] ?? null,
                    ]);

                    if (!$subsectionId) {
                        throw new \RuntimeException('Failed to insert subsection');
                    }

                    foreach ($subsectionData['questions'] ?? [] as $questionData) {
                        $inserted = $this->questionModel->insert([
                            'subsection_id' => $subsectionId,
                            'question_id' => $questionData['id'],
                            'order' => $questionData['order'],
                            'text' => $questionData['text'],
                            'type' => $questionData['type'],
                            'required' => $questionData['required'] ? 1 : 0,
                            'config' => !empty($questionData['config']) ? json_encode($questionData['config']) : null,
                            'conditional_logic' => !empty($questionData['conditionalLogic']) ? json_encode($questionData['conditionalLogic']) : null,
                            'table_config' => !empty($questionData['tableConfig']) ? json_encode($questionData['tableConfig']) : null,
                        ]);

                        if (!$inserted) {
                            throw new \RuntimeException('Failed to insert question');
                        }
                    }
                }
            }

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Failed to save template structure: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if template has v2.0 structure
     */
    public function hasV2Structure(int $templateId): bool
    {
        return $this->sectionModel->where('template_id', $templateId)
                                 ->countAllResults() > 0;
    }

    /**
     * Delete template structure (cascade will handle subsections and questions)
     */
    public function deleteTemplateStructure(int $templateId): bool
    {
        return $this->sectionModel->deleteSectionsByTemplateId($templateId);
    }
}
