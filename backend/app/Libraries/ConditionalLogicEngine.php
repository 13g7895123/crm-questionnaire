<?php

namespace App\Libraries;

/**
 * Conditional Logic Engine
 * Evaluates conditions and determines which questions should be visible/required
 */
class ConditionalLogicEngine
{
    /**
     * Evaluate a single condition
     * 
     * @param array $condition ['operator' => string, 'value' => mixed]
     * @param mixed $answer The actual answer value
     * @return bool
     */
    public function evaluateCondition(array $condition, $answer): bool
    {
        $operator = $condition['operator'] ?? 'equals';
        $expectedValue = $condition['value'] ?? null;
        
        switch ($operator) {
            case 'equals':
                return $this->isEqual($answer, $expectedValue);
                
            case 'notEquals':
                return !$this->isEqual($answer, $expectedValue);
                
            case 'contains':
                if (is_array($answer)) {
                    return in_array($expectedValue, $answer);
                }
                if (is_string($answer)) {
                    return strpos($answer, (string)$expectedValue) !== false;
                }
                return false;
                
            case 'greaterThan':
                return is_numeric($answer) && is_numeric($expectedValue) && $answer > $expectedValue;
                
            case 'lessThan':
                return is_numeric($answer) && is_numeric($expectedValue) && $answer < $expectedValue;
                
            case 'greaterThanOrEqual':
                return is_numeric($answer) && is_numeric($expectedValue) && $answer >= $expectedValue;
                
            case 'lessThanOrEqual':
                return is_numeric($answer) && is_numeric($expectedValue) && $answer <= $expectedValue;
                
            case 'isEmpty':
                return empty($answer);
                
            case 'isNotEmpty':
                return !empty($answer);
                
            default:
                return false;
        }
    }

    /**
     * Get visible questions based on conditional logic
     * 
     * @param array $templateStructure Complete template structure with sections/subsections/questions
     * @param array $answers Current answers ['questionId' => ['value' => mixed]]
     * @return array Array of visible question IDs
     */
    public function getVisibleQuestions(array $templateStructure, array $answers): array
    {
        $visibleQuestions = [];
        
        foreach ($templateStructure as $section) {
            foreach ($section['subsections'] ?? [] as $subsection) {
                foreach ($subsection['questions'] ?? [] as $question) {
                    // Check if question should be shown based on conditional logic
                    if ($this->shouldShowQuestion($question, $answers, $visibleQuestions)) {
                        $visibleQuestions[] = $question['id'];
                        
                        // Add follow-up questions if condition is met
                        $followUpQuestions = $this->getFollowUpQuestions($question, $answers);
                        $visibleQuestions = array_merge($visibleQuestions, $followUpQuestions);
                    }
                }
            }
        }
        
        return array_unique($visibleQuestions);
    }

    /**
     * Get required questions based on conditional logic
     * 
     * @param array $templateStructure Complete template structure
     * @param array $answers Current answers
     * @return array Array of required question IDs
     */
    public function getRequiredQuestions(array $templateStructure, array $answers): array
    {
        $requiredQuestions = [];
        $visibleQuestions = $this->getVisibleQuestions($templateStructure, $answers);
        
        foreach ($templateStructure as $section) {
            foreach ($section['subsections'] ?? [] as $subsection) {
                foreach ($subsection['questions'] ?? [] as $question) {
                    // Only check if question is visible
                    if (in_array($question['id'], $visibleQuestions)) {
                        if ($question['required'] ?? false) {
                            $requiredQuestions[] = $question['id'];
                        }
                        
                        // Check follow-up questions
                        $followUpRequired = $this->getRequiredFollowUpQuestions($question, $answers);
                        $requiredQuestions = array_merge($requiredQuestions, $followUpRequired);
                    }
                }
            }
        }
        
        return array_unique($requiredQuestions);
    }

    /**
     * Check if a question should be shown
     */
    protected function shouldShowQuestion(array $question, array $answers, array $visibleQuestions): bool
    {
        $conditionalLogic = $question['conditionalLogic'] ?? null;
        
        // If no conditional logic, always show
        if (!$conditionalLogic || !isset($conditionalLogic['showWhen'])) {
            return true;
        }
        
        $showWhen = $conditionalLogic['showWhen'];
        $dependentQuestionId = $showWhen['questionId'] ?? null;
        
        // If dependent question is not visible, don't show this question
        if ($dependentQuestionId && !in_array($dependentQuestionId, $visibleQuestions)) {
            return false;
        }
        
        // Check if condition is met
        $dependentAnswer = $answers[$dependentQuestionId]['value'] ?? null;
        return $this->evaluateCondition($showWhen['condition'] ?? [], $dependentAnswer);
    }

    /**
     * Get follow-up questions that should be shown
     */
    protected function getFollowUpQuestions(array $question, array $answers): array
    {
        $followUpQuestions = [];
        $conditionalLogic = $question['conditionalLogic'] ?? null;
        
        if (!$conditionalLogic || empty($conditionalLogic['followUpQuestions'])) {
            return [];
        }
        
        $currentAnswer = $answers[$question['id']]['value'] ?? null;
        
        foreach ($conditionalLogic['followUpQuestions'] as $followUpRule) {
            $condition = $followUpRule['condition'] ?? null;
            
            if ($condition && $this->evaluateCondition($condition, $currentAnswer)) {
                foreach ($followUpRule['questions'] ?? [] as $followUpQuestion) {
                    $followUpQuestions[] = $followUpQuestion['id'];
                    
                    // Recursively check nested follow-ups
                    $nestedFollowUps = $this->getFollowUpQuestions($followUpQuestion, $answers);
                    $followUpQuestions = array_merge($followUpQuestions, $nestedFollowUps);
                }
            }
        }
        
        return $followUpQuestions;
    }

    /**
     * Get required follow-up questions
     */
    protected function getRequiredFollowUpQuestions(array $question, array $answers): array
    {
        $requiredQuestions = [];
        $conditionalLogic = $question['conditionalLogic'] ?? null;
        
        if (!$conditionalLogic || empty($conditionalLogic['followUpQuestions'])) {
            return [];
        }
        
        $currentAnswer = $answers[$question['id']]['value'] ?? null;
        
        foreach ($conditionalLogic['followUpQuestions'] as $followUpRule) {
            $condition = $followUpRule['condition'] ?? null;
            
            if ($condition && $this->evaluateCondition($condition, $currentAnswer)) {
                foreach ($followUpRule['questions'] ?? [] as $followUpQuestion) {
                    if ($followUpQuestion['required'] ?? false) {
                        $requiredQuestions[] = $followUpQuestion['id'];
                    }
                    
                    // Recursively check nested follow-ups
                    $nestedRequired = $this->getRequiredFollowUpQuestions($followUpQuestion, $answers);
                    $requiredQuestions = array_merge($requiredQuestions, $nestedRequired);
                }
            }
        }
        
        return $requiredQuestions;
    }

    /**
     * Compare two values for equality (handles different types)
     */
    protected function isEqual($value1, $value2): bool
    {
        // Handle boolean comparison
        if (is_bool($value1) || is_bool($value2)) {
            return (bool)$value1 === (bool)$value2;
        }
        
        // Handle numeric comparison
        if (is_numeric($value1) && is_numeric($value2)) {
            return (float)$value1 === (float)$value2;
        }
        
        // Handle string comparison
        return (string)$value1 === (string)$value2;
    }

    /**
     * Clean answers when a dependent question's answer changes
     * Removes answers for follow-up questions that are no longer applicable
     * 
     * @param string $changedQuestionId The question that was changed
     * @param array $templateStructure Complete template structure
     * @param array $answers Current answers
     * @return array Question IDs that should be cleared
     */
    public function getAnswersToClear(string $changedQuestionId, array $templateStructure, array $answers): array
    {
        $toClear = [];
        
        // Find the question in structure
        $changedQuestion = null;
        foreach ($templateStructure as $section) {
            foreach ($section['subsections'] ?? [] as $subsection) {
                foreach ($subsection['questions'] ?? [] as $question) {
                    if ($question['id'] === $changedQuestionId) {
                        $changedQuestion = $question;
                        break 3;
                    }
                }
            }
        }
        
        if (!$changedQuestion) {
            return [];
        }
        
        // Get current follow-up questions
        $currentFollowUps = $this->getFollowUpQuestions($changedQuestion, $answers);
        
        // Get all possible follow-up questions
        $allPossibleFollowUps = $this->getAllPossibleFollowUpQuestions($changedQuestion);
        
        // Questions that should be cleared are those that are possible but not currently applicable
        $toClear = array_diff($allPossibleFollowUps, $currentFollowUps);
        
        return array_values($toClear);
    }

    /**
     * Get all possible follow-up questions (regardless of condition)
     */
    protected function getAllPossibleFollowUpQuestions(array $question): array
    {
        $allFollowUps = [];
        $conditionalLogic = $question['conditionalLogic'] ?? null;
        
        if (!$conditionalLogic || empty($conditionalLogic['followUpQuestions'])) {
            return [];
        }
        
        foreach ($conditionalLogic['followUpQuestions'] as $followUpRule) {
            foreach ($followUpRule['questions'] ?? [] as $followUpQuestion) {
                $allFollowUps[] = $followUpQuestion['id'];
                
                // Recursively get nested follow-ups
                $nestedFollowUps = $this->getAllPossibleFollowUpQuestions($followUpQuestion);
                $allFollowUps = array_merge($allFollowUps, $nestedFollowUps);
            }
        }
        
        return $allFollowUps;
    }
}
