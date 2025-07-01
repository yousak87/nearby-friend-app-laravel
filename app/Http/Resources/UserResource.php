<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'dob' => $this->dob,
            'address' => $this->address,
            'description' => $this->description,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'messge' => $this->message
        ];
    }
}
