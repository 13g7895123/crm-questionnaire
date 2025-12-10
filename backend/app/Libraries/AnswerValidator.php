<?php

namespace App\Libraries;

use App\Repositories\ProjectBasicInfoRepository;

/**
 * Answer Validator
 * Validates answers for templates, including basic info, table answers, and conditional logic
 */
class AnswerValidator
{
    protected ConditionalLogicEngine $conditionalEngine;
    protected ProjectBasicInfoRepository $basicInfoRepo;

    public function __construct()
    {
        $this->conditionalEngine = new ConditionalLogicEngine();
        $this->basicInfoRepo = new ProjectBasicInfoRepository();
    }

    /**
     * Validate basic info data
     * 
     * @param array $basicInfo
     * @return array Array of validation errors
     */
    public function validateBasicInfo(array $basicInfo): array
    {
        return $this->basicInfoRepo->validateBasicInfoData($basicInfo);
    }

    /**
     * Validate table answer
     * 
     * @param array $tableAnswer
     * @param array $tableConfig Table configuration from question
     * @return array Array of validation errors
     */
    public function validateTableAnswer(array $tableAnswer, array $tableConfig): array
    {
        $errors = [];
        
        if (!is_array($tableAnswer)) {
            return ['table' => '表格資料格式錯誤'];
        }
        
        $columns = $tableConfig['columns'] ?? [];
        $minRows = $tableConfig['minRows'] ?? 0;
        $maxRows = $tableConfig['maxRows'] ?? 999;
        
        // Check row count
        $rowCount = count($tableAnswer);
        if ($rowCount < $minRows) {
            $errors['rows'] = "至少需要 {$minRows} 筆資料";
        }
        if ($rowCount > $maxRows) {
            $errors['rows'] = "最多只能 {$maxRows} 筆資料";
        }
        
        // Validate each row
        foreach ($tableAnswer as $rowIndex => $row) {
            if (!is_array($row)) {
                $errors["row_{$rowIndex}"] = "第 " . ($rowIndex + 1) . " 筆資料格式錯誤";
                continue;
            }
            
            // Check required columns
            foreach ($columns as $column) {
                $columnId = $column['id'];
                $columnLabel = $column['label'] ?? $columnId;
                
                if (($column['required'] ?? false) && empty($row[$columnId])) {
                    $errors["row_{$rowIndex}_{$columnId}"] = "第 " . ($rowIndex + 1) . " 筆的「{$columnLabel}」為必填";
                }
                
                // Validate column type
                if (!empty($row[$columnId])) {
                    $columnType = $column['type'] ?? 'text';
                    $value = $row[$columnId];
                    
                    switch ($columnType) {
                        case 'number':
                            if (!is_numeric($value)) {
                                $errors["row_{$rowIndex}_{$columnId}"] = "第 " . ($rowIndex + 1) . " 筆的「{$columnLabel}」必須為數字";
                            }
                            break;
                            
                        case 'date':
                            if (!$this->isValidDate($value)) {
                                $errors["row_{$rowIndex}_{$columnId}"] = "第 " . ($rowIndex + 1) . " 筆的「{$columnLabel}」日期格式錯誤";
                            }
                            break;
                            
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors["row_{$rowIndex}_{$columnId}"] = "第 " . ($rowIndex + 1) . " 筆的「{$columnLabel}」電子郵件格式錯誤";
                            }
                            break;
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Validate conditional logic
     * Ensures that answers are consistent with conditional logic rules
     * 
     * @param array $templateStructure
     * @param array $answers
     * @return array Array of validation errors
     */
    public function validateConditionalLogic(array $templateStructure, array $answers): array
    {
        $errors = [];
        $visibleQuestions = $this->conditionalEngine->getVisibleQuestions($templateStructure, $answers);
        
        // Check if there are answers for questions that should not be visible
        foreach ($answers as $questionId => $answerData) {
            if (!in_array($questionId, $visibleQuestions)) {
                // Find question details
                $question = $this->findQuestionInStructure($questionId, $templateStructure);
                if ($question && ($question['conditionalLogic'] ?? null)) {
                    $errors[$questionId] = "此題目根據條件邏輯不應顯示，請刪除答案";
                }
            }
        }
        
        return $errors;
    }

    /**
     * Validate required fields considering conditional logic
     * 
     * @param array $templateStructure
     * @param array $answers
     * @return array Array of missing required question IDs with details
     */
    public function validateRequiredFields(array $templateStructure, array $answers): array
    {
        $missing = [];
        $requiredQuestions = $this->conditionalEngine->getRequiredQuestions($templateStructure, $answers);
        
        foreach ($requiredQuestions as $questionId) {
            $answerValue = $answers[$questionId]['value'] ?? null;
            
            if ($this->isAnswerEmpty($answerValue)) {
                $question = $this->findQuestionInStructure($questionId, $templateStructure);
                $missing[] = [
                    'questionId' => $questionId,
                    'questionText' => $question['text'] ?? '',
                ];
            }
        }
        
        return $missing;
    }

    /**
     * Validate all answers before submission
     * 
     * @param array $templateStructure
     * @param array $answers
     * @param array|null $basicInfo
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateForSubmission(array $templateStructure, array $answers, ?array $basicInfo = null): array
    {
        $allErrors = [];
        
        // Validate basic info if provided
        if ($basicInfo !== null) {
            $basicInfoErrors = $this->validateBasicInfo($basicInfo);
            if (!empty($basicInfoErrors)) {
                $allErrors['basicInfo'] = $basicInfoErrors;
            }
        }
        
        // Validate required fields
        $missingRequired = $this->validateRequiredFields($templateStructure, $answers);
        if (!empty($missingRequired)) {
            $allErrors['missingRequired'] = $missingRequired;
        }
        
        // Validate conditional logic
        $conditionalErrors = $this->validateConditionalLogic($templateStructure, $answers);
        if (!empty($conditionalErrors)) {
            $allErrors['conditionalLogic'] = $conditionalErrors;
        }
        
        // Validate table answers
        foreach ($templateStructure as $section) {
            foreach ($section['subsections'] ?? [] as $subsection) {
                foreach ($subsection['questions'] ?? [] as $question) {
                    if ($question['type'] === 'TABLE' && isset($answers[$question['id']])) {
                        $tableAnswer = $answers[$question['id']]['value'] ?? [];
                        $tableConfig = $question['tableConfig'] ?? [];
                        
                        $tableErrors = $this->validateTableAnswer($tableAnswer, $tableConfig);
                        if (!empty($tableErrors)) {
                            $allErrors['table_' . $question['id']] = $tableErrors;
                        }
                    }
                }
            }
        }
        
        return [
            'valid' => empty($allErrors),
            'errors' => $allErrors,
        ];
    }

    /**
     * Check if answer value is empty
     */
    protected function isAnswerEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }
        if ($value === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * Find question in template structure
     */
    protected function findQuestionInStructure(string $questionId, array $templateStructure): ?array
    {
        foreach ($templateStructure as $section) {
            foreach ($section['subsections'] ?? [] as $subsection) {
                foreach ($subsection['questions'] ?? [] as $question) {
                    if ($question['id'] === $questionId) {
                        return $question;
                    }
                    
                    // Check follow-up questions
                    $foundInFollowUp = $this->findQuestionInFollowUps($questionId, $question);
                    if ($foundInFollowUp) {
                        return $foundInFollowUp;
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Recursively find question in follow-up questions
     */
    protected function findQuestionInFollowUps(string $questionId, array $question): ?array
    {
        $conditionalLogic = $question['conditionalLogic'] ?? null;
        
        if (!$conditionalLogic || empty($conditionalLogic['followUpQuestions'])) {
            return null;
        }
        
        foreach ($conditionalLogic['followUpQuestions'] as $followUpRule) {
            foreach ($followUpRule['questions'] ?? [] as $followUpQuestion) {
                if ($followUpQuestion['id'] === $questionId) {
                    return $followUpQuestion;
                }
                
                // Recursively search
                $found = $this->findQuestionInFollowUps($questionId, $followUpQuestion);
                if ($found) {
                    return $found;
                }
            }
        }
        
        return null;
    }

    /**
     * Validate date format
     */
    protected function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
