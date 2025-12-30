<?php

namespace App\Repositories;

use App\Models\TemplateSectionModel;
use App\Models\TemplateSubsectionModel;
use App\Models\TemplateQuestionModel;
use App\Models\TemplateTranslationModel;

class TemplateStructureRepository
{
    protected TemplateSectionModel $sectionModel;
    protected TemplateSubsectionModel $subsectionModel;
    protected TemplateQuestionModel $questionModel;
    protected TemplateTranslationModel $translationModel;

    public function __construct()
    {
        $this->sectionModel = new TemplateSectionModel();
        $this->subsectionModel = new TemplateSubsectionModel();
        $this->questionModel = new TemplateQuestionModel();
        $this->translationModel = new TemplateTranslationModel();
    }

    /**
     * Get complete template structure with sections, subsections, and questions
     * 
     * @param int $templateId
     * @param string|null $locale Language locale (en, zh). If null, returns default titles.
     */
    public function getTemplateStructure(int $templateId, ?string $locale = null): array
    {
        // Batch load all sections
        $sections = $this->sectionModel->where('template_id', $templateId)
            ->orderBy('order', 'ASC')
            ->findAll();

        if (empty($sections)) {
            return [];
        }

        $sectionIds = array_map(fn($s) => $s->id, $sections);
        $sectionIdMap = array_column($sections, null, 'id');

        // Batch load all subsections
        $subsections = $this->subsectionModel->whereIn('section_id', $sectionIds)
            ->orderBy('order', 'ASC')
            ->findAll();

        $subsectionIds = array_map(fn($s) => $s->id, $subsections);
        $subsectionIdMap = array_column($subsections, null, 'id');

        // Batch load all questions
        $questions = [];
        if (!empty($subsectionIds)) {
            $questions = $this->questionModel->whereIn('subsection_id', $subsectionIds)
                ->orderBy('order', 'ASC')
                ->findAll();
        }

        // Batch load all translations if locale is specified
        $translations = [];
        if ($locale) {
            // Get all translations in one query
            $allEntityIds = [
                'section' => $sectionIds,
                'subsection' => $subsectionIds,
                'question' => array_map(fn($q) => $q->id, $questions),
            ];
            $translations = $this->batchLoadTranslations($allEntityIds, $locale);
        }

        // Build structure from bottom up
        // Group questions by subsection_id
        $questionsBySubsection = [];
        foreach ($questions as $question) {
            $qData = $question->toApiResponse();

            // Apply translation
            if (isset($translations['question'][$question->id]['text'])) {
                $qData['text'] = $translations['question'][$question->id]['text'];
            }

            $questionsBySubsection[$question->subsection_id][] = $qData;
        }

        // Group subsections by section_id
        $subsectionsBySection = [];
        foreach ($subsections as $subsection) {
            $ssData = $subsection->toApiResponse();

            // Apply translation
            if (isset($translations['subsection'][$subsection->id]['title'])) {
                $ssData['title'] = $translations['subsection'][$subsection->id]['title'];
            }

            $ssData['questions'] = $questionsBySubsection[$subsection->id] ?? [];
            $subsectionsBySection[$subsection->section_id][] = $ssData;
        }

        // Build final structure
        $structure = [];
        foreach ($sections as $section) {
            $sData = $section->toApiResponse();

            // Apply translation
            if (isset($translations['section'][$section->id]['title'])) {
                $sData['title'] = $translations['section'][$section->id]['title'];
            }

            $sData['subsections'] = $subsectionsBySection[$section->id] ?? [];
            $structure[] = $sData;
        }

        return $structure;
    }

    /**
     * Batch load translations for multiple entity types
     */
    protected function batchLoadTranslations(array $allEntityIds, string $locale): array
    {
        $result = [
            'section' => [],
            'subsection' => [],
            'question' => [],
        ];

        foreach ($allEntityIds as $entityType => $ids) {
            if (empty($ids)) continue;

            $translations = $this->translationModel->getTranslationsForEntities($entityType, $ids, $locale);
            foreach ($translations as $t) {
                $result[$entityType][$t->entity_id][$t->field] = $t->translated_text;
            }
        }

        return $result;
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
            // Delete existing structure and translations
            $this->deleteTemplateStructureWithTranslations($templateId);

            // Insert new structure
            foreach ($sections as $sectionIndex => $sectionData) {
                $sectionInsertData = [
                    'template_id' => $templateId,
                    'section_id' => $sectionData['id'],
                    'order' => $sectionData['order'] ?? ($sectionIndex + 1),
                    'title' => $sectionData['title'],
                    'description' => $sectionData['description'] ?? null,
                ];

                $sectionId = $this->sectionModel->insert($sectionInsertData);

                if (!$sectionId) {
                    $errors = $this->sectionModel->errors();
                    log_message('error', 'Failed to insert section: ' . json_encode($errors) . ' Data: ' . json_encode($sectionInsertData));
                    throw new \RuntimeException('Failed to insert section: ' . json_encode($errors));
                }

                // Save section translations
                $this->saveTranslations('section', $sectionId, [
                    'en' => ['title' => $sectionData['title_en'] ?? $sectionData['title']],
                    'zh' => ['title' => $sectionData['title_zh'] ?? $sectionData['title']],
                ]);

                foreach ($sectionData['subsections'] ?? [] as $subsectionIndex => $subsectionData) {
                    $subsectionInsertData = [
                        'section_id' => $sectionId,
                        'subsection_id' => $subsectionData['id'],
                        'order' => $subsectionData['order'] ?? ($subsectionIndex + 1),
                        'title' => $subsectionData['title'],
                        'description' => $subsectionData['description'] ?? null,
                    ];

                    $subsectionId = $this->subsectionModel->insert($subsectionInsertData);

                    if (!$subsectionId) {
                        $errors = $this->subsectionModel->errors();
                        log_message('error', 'Failed to insert subsection: ' . json_encode($errors) . ' Data: ' . json_encode($subsectionInsertData));
                        throw new \RuntimeException('Failed to insert subsection: ' . json_encode($errors));
                    }

                    // Save subsection translations
                    $this->saveTranslations('subsection', $subsectionId, [
                        'en' => ['title' => $subsectionData['title_en'] ?? $subsectionData['title']],
                        'zh' => ['title' => $subsectionData['title_zh'] ?? $subsectionData['title']],
                    ]);

                    foreach ($subsectionData['questions'] ?? [] as $questionIndex => $questionData) {
                        $questionInsertData = [
                            'subsection_id' => $subsectionId,
                            'question_id' => $questionData['id'],
                            'order' => $questionData['order'] ?? ($questionIndex + 1),
                            'text' => $questionData['text'],
                            'type' => $questionData['type'],
                            'required' => $questionData['required'] ? 1 : 0,
                            'config' => !empty($questionData['config']) ? json_encode($questionData['config']) : null,
                            'conditional_logic' => !empty($questionData['conditionalLogic']) ? json_encode($questionData['conditionalLogic']) : null,
                            'table_config' => !empty($questionData['tableConfig']) ? json_encode($questionData['tableConfig']) : null,
                        ];

                        $questionId = $this->questionModel->insert($questionInsertData);

                        if (!$questionId) {
                            $errors = $this->questionModel->errors();
                            log_message('error', 'Failed to insert question: ' . json_encode($errors) . ' Data: ' . json_encode($questionInsertData));
                            throw new \RuntimeException('Failed to insert question: ' . json_encode($errors));
                        }

                        // Save question translations
                        $this->saveTranslations('question', $questionId, [
                            'en' => ['text' => $questionData['text_en'] ?? $questionData['text']],
                            'zh' => ['text' => $questionData['text_zh'] ?? $questionData['text']],
                        ]);
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
     * Save translations for an entity
     */
    protected function saveTranslations(string $type, int $id, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            foreach ($fields as $field => $value) {
                if (!empty($value)) {
                    $this->translationModel->saveTranslation($type, $id, $locale, $field, $value);
                }
            }
        }
    }

    /**
     * Delete template structure with translations
     */
    protected function deleteTemplateStructureWithTranslations(int $templateId): void
    {
        // Get all section IDs for this template
        $sections = $this->sectionModel->where('template_id', $templateId)->findAll();
        $sectionIds = array_map(fn($s) => $s->id, $sections);

        if (!empty($sectionIds)) {
            // Get all subsection IDs
            $subsections = $this->subsectionModel->whereIn('section_id', $sectionIds)->findAll();
            $subsectionIds = array_map(fn($s) => $s->id, $subsections);

            if (!empty($subsectionIds)) {
                // Get all question IDs and delete translations
                $questions = $this->questionModel->whereIn('subsection_id', $subsectionIds)->findAll();
                $questionIds = array_map(fn($q) => $q->id, $questions);

                if (!empty($questionIds)) {
                    $this->translationModel->deleteTranslationsForEntities('question', $questionIds);
                }
            }

            // Delete subsection translations
            if (!empty($subsectionIds)) {
                $this->translationModel->deleteTranslationsForEntities('subsection', $subsectionIds);
            }

            // Delete section translations
            $this->translationModel->deleteTranslationsForEntities('section', $sectionIds);
        }

        // Delete main records (cascade will handle subsections and questions)
        $this->sectionModel->deleteSectionsByTemplateId($templateId);
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
