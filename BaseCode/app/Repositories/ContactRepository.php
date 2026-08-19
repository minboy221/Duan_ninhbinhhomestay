<?php

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

class ContactRepository
{
    /**
     * Get all contact submissions
     */
    public function getAll(): Collection
    {
        return Contact::with('user')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Find contact by ID
     */
    public function findById(int $id): ?Contact
    {
        return Contact::find($id);
    }

    /**
     * Create new contact submission
     */
    public function create(array $data): Contact
    {
        return Contact::create($data);
    }

    /**
     * Update contact model
     */
    public function update(Contact $contact, array $data): bool
    {
        return $contact->update($data);
    }

    /**
     * Delete contact model
     */
    public function delete(Contact $contact): bool
    {
        return $contact->delete();
    }
}
