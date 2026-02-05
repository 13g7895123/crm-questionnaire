<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\RmSupplierAssignmentModel;
use App\Models\RmAnswerModel;
use App\Models\RmAnswerSmelterModel;
use App\Models\RmAnswerMineModel;
use App\Models\RmSmelterModel;
use CodeIgniter\API\ResponseTrait;

class RmQuestionnaires extends BaseController
{
    use ResponseTrait;

    protected $assignmentModel;
    protected $answerModel;

    public function __construct()
    {
        $this->assignmentModel = new RmSupplierAssignmentModel();
        $this->answerModel = new RmAnswerModel();
    }

    /**
     * 取得供應商的問卷填寫內容
     */
    public function show($assignmentId = null)
    {
        try {
            $assignment = $this->assignmentModel->find($assignmentId);
            if (!$assignment) {
                return $this->failNotFound('找不到指派記錄');
            }

            // JOIN project 表以取得專案類型資訊
            $projectModel = new \App\Models\RmProjectModel();
            $project = $projectModel->find($assignment['project_id']);
            if ($project) {
                $assignment['project'] = $project;
            }

            // 取得已有的回答
            $answers = $this->answerModel->where('assignment_id', $assignmentId)->findAll();

            // 對於每一個回答，取得其冶煉廠與礦場清單
            $smelterModel = new RmAnswerSmelterModel();
            $mineModel = new RmAnswerMineModel();

            foreach ($answers as &$answer) {
                $answer['smelters'] = $smelterModel->where('answer_id', $answer['id'])->findAll();
                if ($answer['template_type'] === 'EMRT') {
                    $answer['mines'] = $mineModel->where('answer_id', $answer['id'])->findAll();
                }
            }

            return $this->respond([
                'success' => true,
                'data'    => [
                    'assignment' => $assignment,
                    'answers'    => $answers
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 匯入問卷 Excel (CMRT/EMRT/AMRT)
     */
    public function import($assignmentId = null)
    {
        try {
            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid()) {
                return $this->failValidationError('無效的檔案');
            }

            // 1. 偵測檔案類型
            $detector = new \App\Libraries\RM\RMITemplateDetector();
            $detection = $detector->detect($file->getTempName());

            if (!$detection['success']) {
                return $this->failValidationError('無法辨識此 Excel 檔案，請確保使用 RMI 官方範本。');
            }

            $templateType = $detection['type'];

            // 2. 進行解析
            $parser = null;
            if ($templateType === 'CMRT') {
                $parser = new \App\Libraries\RM\CMRTParser($file->getTempName());
            } elseif ($templateType === 'EMRT') {
                $parser = new \App\Libraries\RM\EMRTParser($file->getTempName());
            } elseif ($templateType === 'AMRT') {
                $parser = new \App\Libraries\RM\AMRTParser($file->getTempName());
            } else {
                return $this->failValidationError('目前僅支援 CMRT/EMRT/AMRT 匯入。');
            }

            $results = $parser->parse();

            // 3. 儲存至資料庫
            $db = \Config\Database::connect();
            $db->transStart();

            // 檢查是否已存在該類型的回答
            $answer = $this->answerModel->where('assignment_id', $assignmentId)
                ->where('template_type', $templateType)
                ->first();

            $dbData = [
                'assignment_id'         => $assignmentId,
                'template_type'         => $templateType,
                'company_name'          => $results['declaration']['companyName'] ?? '',
                'company_country'       => $results['declaration']['companyCountry'] ?? '',
                'company_address'       => $results['declaration']['companyAddress'] ?? '',
                'company_contact_name'  => $results['declaration']['companyContactName'] ?? '',
                'company_contact_email' => $results['declaration']['companyContactEmail'] ?? '',
                'company_contact_phone' => $results['declaration']['companyContactPhone'] ?? '',
                'authorizer'            => $results['declaration']['authorizer'] ?? '',
                'effective_date'        => $results['declaration']['effectiveDate'] ?? null,
                'declaration_scope'     => $results['declaration']['declarationScope'] ?? '',
                'mineral_declaration'   => json_encode($results['mineralDeclaration'] ?? []),
                'policy_answers'        => json_encode($results['mineralDeclaration']['policy'] ?? []),
            ];

            if ($answer) {
                $this->answerModel->update($answer['id'], $dbData);
                $answerId = $answer['id'];
            } else {
                $this->answerModel->insert($dbData);
                $answerId = $this->answerModel->getInsertID();
            }

            // 處理冶煉廠清單
            $smelterAnswerModel = new RmAnswerSmelterModel();
            $smelterAnswerModel->where('answer_id', $answerId)->delete();

            $smelterRefModel = new RmSmelterModel();

            foreach ($results['smelterList'] as $row) {
                // 嘗試比對主檔
                $refSmelter = $smelterRefModel->where('smelter_id', $row['smelter_id'])->first();

                $smelterAnswerModel->insert([
                    'answer_id'              => $answerId,
                    'metal_type'             => $row['metal_type'],
                    'smelter_id'             => $row['smelter_id'],
                    'smelter_name'           => $row['smelter_name'],
                    'smelter_country'        => $row['smelter_country'],
                    'source_of_smelter_id'   => $row['source_of_smelter_id'],
                    'is_conformant_expected' => 0,
                    'rmi_smelter_id'         => $refSmelter ? $refSmelter['id'] : null,
                ]);
            }

            // 處理礦場清單 (僅 EMRT)
            if ($templateType === 'EMRT' && isset($results['mineList'])) {
                $mineAnswerModel = new RmAnswerMineModel();
                $mineAnswerModel->where('answer_id', $answerId)->delete();

                foreach ($results['mineList'] as $row) {
                    $mineAnswerModel->insert([
                        'answer_id'     => $answerId,
                        'metal_type'    => $row['metal_type'],
                        'mine_name'     => $row['mine_name'],
                        'mine_country'  => $row['mine_country'],
                        'mine_location' => $row['mine_location'],
                    ]);
                }
            }

            // 更新指派狀態
            $this->assignmentModel->update($assignmentId, ['status' => 'in_progress']);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('資料庫儲存失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => '問卷匯入成功',
                'data'    => [
                    'answerId'     => $answerId,
                    'templateType' => $templateType,
                    'smelterCount' => count($results['smelterList'])
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 手動儲存問卷 (線上表單)
     */
    public function saveManual($assignmentId = null)
    {
        try {
            $input = $this->request->getJSON(true);
            $templateType = $input['template_type'] ?? null;

            if (!$templateType) {
                return $this->failValidationError('未指定範本類型');
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // 檢查是否已存在該類型的回答
            $answer = $this->answerModel->where('assignment_id', $assignmentId)
                ->where('template_type', $templateType)
                ->first();

            $dbData = [
                'assignment_id'       => $assignmentId,
                'template_type'       => $templateType,
                'company_name'        => $input['declaration']['companyName'] ?? '',
                'declaration_scope'  => $input['declaration']['declarationScope'] ?? '',
                'mineral_declaration' => json_encode($input['mineralDeclaration'] ?? []),
            ];

            if ($answer) {
                $this->answerModel->update($answer['id'], $dbData);
                $answerId = $answer['id'];
            } else {
                $this->answerModel->insert($dbData);
                $answerId = $this->answerModel->getInsertID();
            }

            // 處理冶煉廠清單
            $smelterAnswerModel = new RmAnswerSmelterModel();
            $smelterAnswerModel->where('answer_id', $answerId)->delete();

            $smelterRefModel = new RmSmelterModel();

            if (isset($input['smelterList']) && is_array($input['smelterList'])) {
                foreach ($input['smelterList'] as $row) {
                    $refSmelter = $smelterRefModel->where('smelter_id', $row['smelter_id'])->first();

                    $smelterAnswerModel->insert([
                        'answer_id'              => $answerId,
                        'metal_type'             => $row['metal_type'],
                        'smelter_id'             => $row['smelter_id'],
                        'smelter_name'           => $row['smelter_name'],
                        'smelter_country'        => $row['smelter_country'],
                        'source_of_smelter_id'   => $row['source_of_smelter_id'] ?? 'User Entry',
                        'is_conformant_expected' => 0,
                        'rmi_smelter_id'         => $refSmelter ? $refSmelter['id'] : null,
                    ]);
                }
            }

            // 更新指派狀態
            $this->assignmentModel->update($assignmentId, ['status' => 'in_progress']);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('資料庫儲存失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => '問卷儲存成功',
                'data'    => ['answerId' => $answerId]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 提交問卷
     */
    public function submit($assignmentId = null)
    {
        try {
            $assignment = $this->assignmentModel->find($assignmentId);
            if (!$assignment) {
                return $this->failNotFound('找不到指派記錄');
            }

            $input = $this->request->getJSON(true);
            $templateType = $input['template_type'] ?? null;

            if (!$templateType) {
                return $this->failValidationError('未指定提交的範本類型');
            }

            $answer = $this->answerModel->where('assignment_id', $assignmentId)
                ->where('template_type', $templateType)
                ->first();

            if (!$answer) {
                return $this->failNotFound('找不到對應的問卷內容');
            }

            // 更新提交時間
            $this->answerModel->update($answer['id'], [
                'submitted_at' => date('Y-m-d H:i:s')
            ]);

            // 檢查是否所有的指派範本都已提交
            $allAnswers = $this->answerModel->where('assignment_id', $assignmentId)->findAll();
            $requiredCmrt = $assignment['cmrt_required'];
            $requiredEmrt = $assignment['emrt_required'];
            $requiredAmrt = $assignment['amrt_required'];

            $allSubmitted = true;
            $submittedTypes = array_column($allAnswers, 'template_type');

            if ($requiredCmrt && !in_array('CMRT', $submittedTypes)) $allSubmitted = false;
            if ($requiredEmrt && !in_array('EMRT', $submittedTypes)) $allSubmitted = false;
            if ($requiredAmrt && !in_array('AMRT', $submittedTypes)) $allSubmitted = false;

            // 如果全部提交，更新指派狀態為 submitted
            if ($allSubmitted) {
                $this->assignmentModel->update($assignmentId, [
                    'status'       => 'reviewing', // 提交後進入審核中
                    'submitted_at' => date('Y-m-d H:i:s')
                ]);
            }

            return $this->respond([
                'success' => true,
                'message' => '問卷提交成功'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
