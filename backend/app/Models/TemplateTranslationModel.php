<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\TemplateTranslation;

class TemplateTranslationModel extends Model
{
    protected $table = 'template_translations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = TemplateTranslation::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'translatable_type' => 'required|in_list[section,subsection,question]',
        'translatable_id' => 'required|is_natural_no_zero',
        'locale' => 'required|max_length[10]',
        'field' => 'required|max_length[50]',
        'value' => 'required',
    ];

    /**
     * Get translation for a specific entity, field, and locale
     */
    public function getTranslation(string $type, int $id, string $locale, string $field): ?string
    {
        $result = $this->where([
            'translatable_type' => $type,
            'translatable_id' => $id,
            'locale' => $locale,
            'field' => $field,
        ])->first();

        return $result?->value;
    }

    /**
     * Get all translations for an entity
     */
    public function getTranslationsForEntity(string $type, int $id, ?string $locale = null): array
    {
        $builder = $this->where([
            'translatable_type' => $type,
            'translatable_id' => $id,
        ]);

        if ($locale) {
            $builder->where('locale', $locale);
        }

        return $builder->findAll();
    }

    /**
     * Save or update a translation
     */
    public function saveTranslation(string $type, int $id, string $locale, string $field, string $value): bool
    {
        $existing = $this->where([
            'translatable_type' => $type,
            'translatable_id' => $id,
            'locale' => $locale,
            'field' => $field,
        ])->first();

        if ($existing) {
            return $this->update($existing->id, ['value' => $value]);
        }

        return (bool) $this->insert([
            'translatable_type' => $type,
            'translatable_id' => $id,
            'locale' => $locale,
            'field' => $field,
            'value' => $value,
        ]);
    }

    /**
     * Save multiple translations at once
     */
    public function saveTranslations(string $type, int $id, array $translations): bool
    {
        foreach ($translations as $locale => $fields) {
            foreach ($fields as $field => $value) {
                if (!empty($value)) {
                    $this->saveTranslation($type, $id, $locale, $field, $value);
                }
            }
        }
        return true;
    }

    /**
     * Delete all translations for an entity
     */
    public function deleteTranslationsForEntity(string $type, int $id): bool
    {
        return $this->where([
            'translatable_type' => $type,
            'translatable_id' => $id,
        ])->delete();
    }

    /**
     * Delete all translations for multiple entities
     */
    public function deleteTranslationsForEntities(string $type, array $ids): bool
    {
        if (empty($ids)) {
            return true;
        }
        return $this->where('translatable_type', $type)
            ->whereIn('translatable_id', $ids)
            ->delete();
    }
}
