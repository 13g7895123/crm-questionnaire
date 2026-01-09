<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\TemplateSetModel;
use CodeIgniter\API\ResponseTrait;

class TemplateSets extends BaseController
{
    use ResponseTrait;

    protected $model;

    public function __construct()
    {
        $this->model = new TemplateSetModel();
    }

    /**
     * 取得所有範本組
     */
    public function index()
    {
        try {
            $data = $this->model->orderBy('created_at', 'DESC')->findAll();

            // 轉換為前端預期的格式
            foreach ($data as &$set) {
                $set = $this->formatForFrontend($set);
            }

            return $this->respond([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 取得單一範本組詳情
     */
    public function show($id = null)
    {
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return $this->failNotFound('找不到該範本組');
            }

            return $this->respond([
                'success' => true,
                'data'    => $this->formatForFrontend($data)
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 建立新的範本組
     */
    public function create()
    {
        try {
            $rawInput = $this->request->getJSON(true);
            $dbData = $this->formatForBackend($rawInput);

            if (!$this->model->insert($dbData)) {
                return $this->failValidationErrors($this->model->errors());
            }

            return $this->respondCreated([
                'success' => true,
                'message' => '範本組建立成功',
                'id'      => $this->model->getInsertID()
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 更新範本組
     */
    public function update($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('找不到該範本組');
            }

            $rawInput = $this->request->getJSON(true);
            $dbData = $this->formatForBackend($rawInput);

            if (!$this->model->update($id, $dbData)) {
                return $this->failValidationErrors($this->model->errors());
            }

            return $this->respond([
                'success' => true,
                'message' => '範本組更新成功'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 刪除範本組
     */
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('找不到該範本組');
            }

            if (!$this->model->delete($id)) {
                return $this->failServerError('刪除失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => '範本組已刪除'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 將資料格式化為前端預期結構
     */
    private function formatForFrontend($data)
    {
        return [
            'id'          => $data['id'],
            'name'        => $data['name'],
            'year'        => (int)$data['year'],
            'description' => $data['description'],
            'templates'   => [
                'cmrt' => [
                    'enabled' => (bool)$data['cmrt_enabled'],
                    'version' => $data['cmrt_version']
                ],
                'emrt' => [
                    'enabled' => (bool)$data['emrt_enabled'],
                    'version' => $data['emrt_version']
                ],
                'amrt' => [
                    'enabled'  => (bool)$data['amrt_enabled'],
                    'version'  => $data['amrt_version'],
                    'minerals' => $data['amrt_minerals'] ?? []
                ]
            ],
            'createdAt'   => $data['created_at'],
            'updatedAt'   => $data['updated_at']
        ];
    }

    /**
     * 將前端資料格式化為資料庫結構
     */
    private function formatForBackend($input)
    {
        $data = [
            'name'         => $input['name'] ?? '',
            'year'         => $input['year'] ?? date('Y'),
            'description'  => $input['description'] ?? '',
            'cmrt_enabled' => isset($input['templates']['cmrt']['enabled']) ? (int)$input['templates']['cmrt']['enabled'] : 0,
            'cmrt_version' => $input['templates']['cmrt']['version'] ?? null,
            'emrt_enabled' => isset($input['templates']['emrt']['enabled']) ? (int)$input['templates']['emrt']['enabled'] : 0,
            'emrt_version' => $input['templates']['emrt']['version'] ?? null,
            'amrt_enabled' => isset($input['templates']['amrt']['enabled']) ? (int)$input['templates']['amrt']['enabled'] : 0,
            'amrt_version' => $input['templates']['amrt']['version'] ?? null,
            'amrt_minerals' => isset($input['templates']['amrt']['minerals']) ? json_encode($input['templates']['amrt']['minerals']) : null,
        ];

        return array_filter($data, function ($val) {
            return $val !== null;
        });
    }
}
