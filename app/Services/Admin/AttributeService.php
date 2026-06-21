<?php

namespace App\Services\Admin;

use App\Models\Attribute;
use App\Enums\GeneralStatus;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AttributeService
{
    public function getAttributesByFields(array $fieldIds, ?string $search = null)
    {
        $query = Attribute::with('field:id,name');

        if (!empty($fieldIds)) {
            $query->whereIn('fk_field_id', $fieldIds);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('type', 'LIKE', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }

    public function checkDuplicateName(int $fieldId, string $name, ?int $ignoreId = null): void
    {
        $query = Attribute::where('fk_field_id', $fieldId)->where('name', $name);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new UnprocessableEntityHttpException('Nama atribut sudah digunakan.');
        }
    }

    public function createAttribute(array $data): Attribute
    {
        $this->checkDuplicateName($data['fk_field_id'], $data['name']);

        return Attribute::create(array_merge($data, [
            'status' => GeneralStatus::ACTIVE->value
        ]));
    }

    public function updateAttribute(Attribute $attribute, array $data): Attribute
    {
        if (isset($data['name']) && $data['name'] !== $attribute->name) {
            $this->checkDuplicateName($attribute->fk_field_id, $data['name'], $attribute->id);
        }

        $attribute->update($data);
        return $attribute->fresh();
    }

    public function toggleAttributeStatus(Attribute $attribute): Attribute
    {
        $newStatus = $attribute->status === GeneralStatus::ACTIVE->value
            ? GeneralStatus::INACTIVE->value
            : GeneralStatus::ACTIVE->value;

        $attribute->update(['status' => $newStatus]);
        return $attribute->fresh();
    }
}
