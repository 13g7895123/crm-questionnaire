# Template v2.0 Implementation Progress

**Date**: 2025-12-10  
**Status**: ✅ Phase 1 & Phase 2 Completed

---

## ✅ Phase 1: 資料庫結構 (Completed)

### Migration Files Created
1. ✅ `2025-12-10-100000_CreateTemplateSectionsTable.php`
   - Fields: id, template_id, section_id, order, title, description
   - Foreign Key: template_id → templates(id)
   - Unique Key: (template_id, section_id)

2. ✅ `2025-12-10-100001_CreateTemplateSubsectionsTable.php`
   - Fields: id, section_id, subsection_id, order, title, description
   - Foreign Key: section_id → template_sections(id)
   - Unique Key: (section_id, subsection_id)

3. ✅ `2025-12-10-100002_CreateTemplateQuestionsTable.php`
   - Fields: id, subsection_id, question_id, order, text, type, required, config, conditional_logic, table_config
   - Foreign Key: subsection_id → template_subsections(id)
   - Unique Key: (subsection_id, question_id)
   - Supported Types: BOOLEAN, TEXT, NUMBER, RADIO, CHECKBOX, SELECT, DATE, FILE, TABLE

4. ✅ `2025-12-10-100003_CreateProjectBasicInfoTable.php`
   - Fields: id, project_supplier_id, company_name, company_address, employee_count, male_count, female_count, foreign_count, facilities (JSON), certifications (JSON), rba_online_member, contacts (JSON)
   - Foreign Key: project_supplier_id → project_suppliers(id)
   - Unique Key: project_supplier_id

### Entities Created
1. ✅ `app/Entities/TemplateSection.php`
2. ✅ `app/Entities/TemplateSubsection.php`
3. ✅ `app/Entities/TemplateQuestion.php`
4. ✅ `app/Entities/ProjectBasicInfo.php`

### Models Created
1. ✅ `app/Models/TemplateSectionModel.php`
2. ✅ `app/Models/TemplateSubsectionModel.php`
3. ✅ `app/Models/TemplateQuestionModel.php`
4. ✅ `app/Models/ProjectBasicInfoModel.php`

### Test Data
- ✅ `app/Database/Seeds/TemplateSectionsSeeder.php`
  - Sample data for SAQ template (Sections A, B)
  - Includes conditional logic example (A.1.1)
  - Includes TABLE type example (A.2.1)

---

## ✅ Phase 2: API Endpoints (Completed)

### Repositories Created
1. ✅ `app/Repositories/TemplateStructureRepository.php`
   - `getTemplateStructure($templateId)`: Get complete hierarchical structure
   - `saveTemplateStructure($templateId, $sections)`: Save/update structure with transaction
   - `hasV2Structure($templateId)`: Check if template uses v2.0 structure
   - `deleteTemplateStructure($templateId)`: Delete with cascade

2. ✅ `app/Repositories/ProjectBasicInfoRepository.php`
   - `getByProjectSupplierId($id)`: Get basic info
   - `saveBasicInfo($id, $data)`: Save/update basic info
   - `validateBasicInfoData($data)`: Validate company data structure

### Controllers Extended

#### TemplateController (app/Controllers/Api/V1/TemplateController.php)
- ✅ `GET /api/v1/templates/{id}/structure` - Get v2.0 hierarchical structure
- ✅ `PUT /api/v1/templates/{id}/structure` - Save/update v2.0 structure

#### AnswerController (app/Controllers/Api/V1/AnswerController.php)
- ✅ `GET /api/v1/project-suppliers/{id}/basic-info` - Get company basic info
- ✅ `PUT /api/v1/project-suppliers/{id}/basic-info` - Save/update company basic info

### Routes Added (app/Config/Routes.php)
```php
// Template v2.0 Structure
$routes->get('(:segment)/structure', 'TemplateController::getStructure/$1');
$routes->put('(:segment)/structure', 'TemplateController::saveStructure/$1');

// Project Basic Info
$routes->get('(:segment)/basic-info', 'AnswerController::getBasicInfo/$1');
$routes->put('(:segment)/basic-info', 'AnswerController::saveBasicInfo/$1');
```

---

## 📊 API Documentation

### GET /api/v1/templates/{id}/structure

**Response:**
```json
{
  "success": true,
  "data": {
    "templateId": 1,
    "hasV2Structure": true,
    "structure": {
      "includeBasicInfo": true,
      "sections": [
        {
          "id": "A",
          "order": 1,
          "title": "A. 勞工 (Labor)",
          "description": "勞工權益與工作條件評估",
          "subsections": [
            {
              "id": "A.1",
              "order": 1,
              "title": "A.1 僱傭自由選擇",
              "description": null,
              "questions": [
                {
                  "id": "A.1.1",
                  "order": 1,
                  "text": "貴公司是否有制定並執行禁止強迫勞動的政策？",
                  "type": "BOOLEAN",
                  "required": true,
                  "conditionalLogic": {
                    "showWhen": null,
                    "followUpQuestions": [
                      {
                        "condition": {"operator": "equals", "value": true},
                        "questions": [
                          {
                            "id": "A.1.1.1",
                            "text": "請簡述該政策的內容",
                            "type": "TEXT",
                            "required": false,
                            "config": {"maxLength": 500}
                          }
                        ]
                      }
                    ]
                  }
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

### PUT /api/v1/templates/{id}/structure

**Request:**
```json
{
  "sections": [
    {
      "id": "A",
      "order": 1,
      "title": "A. 勞工 (Labor)",
      "description": "勞工權益與工作條件評估",
      "subsections": [...]
    }
  ]
}
```

**Response:** Same as GET structure

### GET /api/v1/project-suppliers/{id}/basic-info

**Response:**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "basicInfo": {
      "companyName": "供應商A公司",
      "companyAddress": "台北市信義區...",
      "employees": {
        "total": 500,
        "male": 300,
        "female": 200,
        "foreign": 50
      },
      "facilities": [
        {
          "location": "台北廠",
          "address": "台北市...",
          "area": 5000,
          "type": "生產"
        }
      ],
      "certifications": ["ISO 9001", "ISO 14001"],
      "rbaOnlineMember": true,
      "contacts": [
        {
          "name": "張三",
          "title": "品質經理",
          "department": "品質部",
          "email": "zhang@example.com",
          "phone": "02-12345678"
        }
      ]
    }
  }
}
```

### PUT /api/v1/project-suppliers/{id}/basic-info

**Request:**
```json
{
  "basicInfo": {
    "companyName": "供應商A公司",
    "companyAddress": "台北市信義區...",
    "employees": {
      "total": 500,
      "male": 300,
      "female": 200,
      "foreign": 50
    },
    "facilities": [...],
    "certifications": [...],
    "rbaOnlineMember": true,
    "contacts": [...]
  }
}
```

**Response:** Same as GET basic-info

---

## 🗄️ Database Schema

### template_sections
```sql
CREATE TABLE `template_sections` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `template_id` INT UNSIGNED NOT NULL,
  `section_id` VARCHAR(10) NOT NULL,
  `order` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `template_id_section_id` (`template_id`, `section_id`),
  KEY `template_id` (`template_id`),
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### template_subsections
```sql
CREATE TABLE `template_subsections` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `section_id` BIGINT UNSIGNED NOT NULL,
  `subsection_id` VARCHAR(20) NOT NULL,
  `order` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `section_id_subsection_id` (`section_id`, `subsection_id`),
  KEY `section_id` (`section_id`),
  FOREIGN KEY (`section_id`) REFERENCES `template_sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### template_questions
```sql
CREATE TABLE `template_questions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subsection_id` BIGINT UNSIGNED NOT NULL,
  `question_id` VARCHAR(50) NOT NULL,
  `order` INT UNSIGNED NOT NULL,
  `text` TEXT NOT NULL,
  `type` ENUM('BOOLEAN','TEXT','NUMBER','RADIO','CHECKBOX','SELECT','DATE','FILE','TABLE') NOT NULL,
  `required` TINYINT(1) NOT NULL DEFAULT 0,
  `config` JSON NULL,
  `conditional_logic` JSON NULL,
  `table_config` JSON NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `subsection_id_question_id` (`subsection_id`, `question_id`),
  KEY `subsection_id` (`subsection_id`),
  FOREIGN KEY (`subsection_id`) REFERENCES `template_subsections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### project_basic_info
```sql
CREATE TABLE `project_basic_info` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_supplier_id` INT UNSIGNED NOT NULL,
  `company_name` VARCHAR(200) NOT NULL,
  `company_address` TEXT NULL,
  `employee_count` INT UNSIGNED NULL,
  `male_count` INT UNSIGNED NULL,
  `female_count` INT UNSIGNED NULL,
  `foreign_count` INT UNSIGNED NULL,
  `facilities` JSON NULL,
  `certifications` JSON NULL,
  `rba_online_member` TINYINT(1) NULL,
  `contacts` JSON NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `project_supplier_id` (`project_supplier_id`),
  FOREIGN KEY (`project_supplier_id`) REFERENCES `project_suppliers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🧪 Testing Commands

### Run Migrations
```bash
docker compose exec backend php spark migrate
```

### Seed Test Data
```bash
docker compose exec backend php spark db:seed TemplateSectionsSeeder
```

### View Tables
```bash
docker compose exec backend php spark db:table template_sections
docker compose exec backend php spark db:table template_subsections
docker compose exec backend php spark db:table template_questions
docker compose exec backend php spark db:table project_basic_info
```

---

## 📝 Next Steps (Phase 3-5)

### Phase 3: 業務邏輯
- Validation rules for conditional logic
- Scoring system for A-E sections
- Backward compatibility handling

### Phase 4: 測試
- Unit tests for Models
- API integration tests
- End-to-end workflow tests

### Phase 5: 整合
- ✅ Frontend integration (Questionnaire Rendering)
- [ ] Data migration from v1.0
- ✅ Documentation updates

---

## 🔍 Key Features Implemented

1. **Hierarchical Structure**: Template → Section → Subsection → Question
2. **Conditional Logic**: Follow-up questions based on answers
3. **Table Questions**: 2D data entry with configurable columns/rows
4. **Company Basic Info**: Fixed structure for SAQ templates
5. **Transaction Safety**: Database operations wrapped in transactions
6. **Cascade Deletes**: Automatic cleanup of related data
7. **API Versioning**: Backward compatible with v1.0 flat structure
8. **JSON Storage**: Flexible configuration and data storage

---

## 📊 Statistics

- **Migration Files**: 4
- **Entity Classes**: 4
- **Model Classes**: 4
- **Repository Classes**: 2
- **API Endpoints**: 4 (2 for templates, 2 for basic info)
- **Database Tables**: 4
- **Lines of Code**: ~1500
