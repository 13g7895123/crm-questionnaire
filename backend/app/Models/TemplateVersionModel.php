<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\TemplateVersion;

class TemplateVersionModel extends Model
{
    protected $table = 'template_versions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = TemplateVersion::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'template_id',
        'version',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false;

    protected $validationRules = [
        'template_id' => 'required',
        'version' => 'required|regex_match[/^\d+\.\d+\.\d+$/]',
    ];

    /**
     * Compare versions using semantic versioning
     */
    public function compareVersions(string $v1, string $v2): int
    {
        $parts1 = array_map('intval', explode('.', $v1));
        $parts2 = array_map('intval', explode('.', $v2));

        for ($i = 0; $i < 3; $i++) {
            if ($parts1[$i] > $parts2[$i]) return 1;
            if ($parts1[$i] < $parts2[$i]) return -1;
        }

        return 0;
    }

    /**
     * Check if version is greater than current latest
     */
    public function isVersionGreater(string $templateId, string $newVersion): bool
    {
        $latest = $this->where('template_id', $templateId)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!$latest) return true;

        return $this->compareVersions($newVersion, $latest->version) > 0;
    }

    /**
     * Get specific version
     */
    public function getVersion(string $templateId, string $version): ?TemplateVersion
    {
        return $this->where('template_id', $templateId)
            ->where('version', $version)
            ->first();
    }
}
