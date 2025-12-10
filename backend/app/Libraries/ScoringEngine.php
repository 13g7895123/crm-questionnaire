<?php

namespace App\Libraries;

use App\Models\AnswerModel;
use App\Models\TemplateQuestionModel;
use App\Repositories\TemplateStructureRepository;

/**
 * Scoring Engine for SAQ Templates
 * Calculates scores for each section (A-E) and total score
 */
class ScoringEngine
{
    protected AnswerModel $answerModel;
    protected TemplateQuestionModel $questionModel;
    protected TemplateStructureRepository $structureRepo;

    public function __construct()
    {
        $this->answerModel = new AnswerModel();
        $this->questionModel = new TemplateQuestionModel();
        $this->structureRepo = new TemplateStructureRepository();
    }

    /**
     * Calculate score for a specific section
     * 
     * @param int $projectSupplierId
     * @param string $sectionId Section identifier (A, B, C, D, E)
     * @return array ['score' => float, 'maxScore' => int, 'answeredCount' => int, 'totalCount' => int]
     */
    public function calculateSectionScore(int $projectSupplierId, string $sectionId): array
    {
        $answers = $this->answerModel->getAnswersForProjectSupplier($projectSupplierId);
        
        // Get all questions for this section
        $questions = $this->getQuestionsForSection($projectSupplierId, $sectionId);
        
        $totalQuestions = count($questions);
        $answeredCount = 0;
        $positiveAnswers = 0;
        
        foreach ($questions as $question) {
            $questionId = $question->question_id;
            
            if (!isset($answers[$questionId])) {
                continue;
            }
            
            $answer = $answers[$questionId]['value'];
            $answeredCount++;
            
            // Scoring logic based on question type
            switch ($question->type) {
                case 'BOOLEAN':
                    if ($answer === true || $answer === 1 || $answer === '1') {
                        $positiveAnswers++;
                    }
                    break;
                    
                case 'RATING':
                    // Assuming rating is 1-5, normalize to 0-1
                    if (is_numeric($answer)) {
                        $positiveAnswers += ($answer / 5);
                    }
                    break;
                    
                case 'NUMBER':
                    // For number questions, consider any positive number as positive
                    if (is_numeric($answer) && $answer > 0) {
                        $positiveAnswers++;
                    }
                    break;
                    
                default:
                    // For other types (TEXT, SELECT, etc.), count as answered
                    if (!empty($answer)) {
                        $positiveAnswers++;
                    }
            }
        }
        
        // Calculate percentage score
        $score = $totalQuestions > 0 ? ($positiveAnswers / $totalQuestions) * 100 : 0;
        
        return [
            'score' => round($score, 2),
            'maxScore' => 100,
            'answeredCount' => $answeredCount,
            'totalCount' => $totalQuestions,
            'completionRate' => $totalQuestions > 0 ? round(($answeredCount / $totalQuestions) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate total score for all sections
     * 
     * @param int $projectSupplierId
     * @return array
     */
    public function calculateTotalScore(int $projectSupplierId): array
    {
        $sectionIds = ['A', 'B', 'C', 'D', 'E'];
        $sectionScores = [];
        $totalScore = 0;
        $weight = 1 / count($sectionIds); // Equal weight for all sections
        
        foreach ($sectionIds as $sectionId) {
            $sectionScore = $this->calculateSectionScore($projectSupplierId, $sectionId);
            $sectionScores[$sectionId] = array_merge($sectionScore, ['weight' => $weight]);
            $totalScore += $sectionScore['score'] * $weight;
        }
        
        return [
            'sections' => $sectionScores,
            'totalScore' => round($totalScore, 2),
            'grade' => $this->getGrade($totalScore),
            'calculatedAt' => date('c'),
        ];
    }

    /**
     * Get score breakdown with details
     * 
     * @param int $projectSupplierId
     * @return array
     */
    public function getScoreBreakdown(int $projectSupplierId): array
    {
        $totalScoreData = $this->calculateTotalScore($projectSupplierId);
        $breakdown = [];
        
        foreach ($totalScoreData['sections'] as $sectionId => $sectionData) {
            $breakdown[$sectionId] = [
                'sectionId' => $sectionId,
                'sectionName' => $this->getSectionName($sectionId),
                'score' => $sectionData['score'],
                'maxScore' => $sectionData['maxScore'],
                'weight' => $sectionData['weight'],
                'answeredCount' => $sectionData['answeredCount'],
                'totalCount' => $sectionData['totalCount'],
                'completionRate' => $sectionData['completionRate'],
            ];
        }
        
        return [
            'breakdown' => $breakdown,
            'totalScore' => $totalScoreData['totalScore'],
            'grade' => $totalScoreData['grade'],
            'calculatedAt' => $totalScoreData['calculatedAt'],
        ];
    }

    /**
     * Get questions for a specific section
     */
    protected function getQuestionsForSection(int $projectSupplierId, string $sectionId): array
    {
        // Get project supplier to find template
        $projectSupplierModel = model('ProjectSupplierModel');
        $projectSupplier = $projectSupplierModel->find($projectSupplierId);
        
        if (!$projectSupplier) {
            return [];
        }
        
        // Get project to find template
        $projectModel = model('ProjectModel');
        $project = $projectModel->find($projectSupplier->project_id);
        
        if (!$project) {
            return [];
        }
        
        // Get questions for this section
        $db = \Config\Database::connect();
        $builder = $db->table('template_questions tq');
        $builder->select('tq.*')
                ->join('template_subsections ts', 'ts.id = tq.subsection_id')
                ->join('template_sections tsec', 'tsec.id = ts.section_id')
                ->where('tsec.template_id', $project->template_id)
                ->where('tsec.section_id', $sectionId)
                ->orderBy('tsec.order, ts.order, tq.order');
        
        $results = $builder->get()->getResult();
        
        // Convert to TemplateQuestion entities
        $questions = [];
        foreach ($results as $row) {
            $question = new \App\Entities\TemplateQuestion((array)$row);
            $questions[] = $question;
        }
        
        return $questions;
    }

    /**
     * Get grade based on total score
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 90) return '優秀';
        if ($score >= 80) return '良好';
        if ($score >= 70) return '合格';
        if ($score >= 60) return '待改進';
        return '不合格';
    }

    /**
     * Get section name
     */
    protected function getSectionName(string $sectionId): string
    {
        $names = [
            'A' => '勞工 (Labor)',
            'B' => '健康與安全 (Health & Safety)',
            'C' => '環境 (Environment)',
            'D' => '道德規範 (Ethics)',
            'E' => '管理系統 (Management System)',
        ];
        
        return $names[$sectionId] ?? $sectionId;
    }
}
