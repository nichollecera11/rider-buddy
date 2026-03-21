<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
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
            'part_name' => strtoupper($this->part_name), // Himoon natong uppercase para nindot
            'slug' => $this->slug,
            'price' => [
                'raw' => $this->price,
                'formatted' => '₱' . number_format($this->price, 2), // Format dayon para sa Mobile App
            ],
            'condition' => ucfirst($this->condition),
            'status' => $this->status,
            'stock' => $this->stock_quantity,
            'is_negotiable' => (bool) $this->is_negotiable,
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'url' => asset('storage/' . $img->path), // I-convert ang path ngadto sa Full URL
            ]),
            'seller_info' => [
                'shop_name' => $this->seller->shop_name,
                'location' => $this->location ?? $this->seller->address,
            ],
            'brand' => $this->brand->name,
            'category' => $this->category->name,
            'posted_at' => $this->created_at->diffForHumans(), // Pananglitan: "2 minutes ago"
        ];
    }
}
