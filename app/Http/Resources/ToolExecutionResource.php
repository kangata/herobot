<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tool_id' => $this->tool_id,
            'tool' => $this->whenLoaded('tool', function () {
                return [
                    'id' => $this->tool->id,
                    'name' => $this->tool->name,
                    'type' => $this->tool->type,
                ];
            }),
            'chat_history_id' => $this->chat_history_id,
            'status' => $this->status,
            'input_parameters' => $this->input_parameters,
            'output' => $this->output,
            'error' => $this->error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
