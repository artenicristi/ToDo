<?php

namespace Carteni\ToDo2;

class Item
{
    public function __construct(
        protected string  $id,
        protected string  $content,
        protected string  $status,
        protected         $dueDate = null,
        protected ?string $createdAt = null
    ) {
        $this->createdAt = $this->createdAt ?? date("d-M-Y H:i:s");
    }

    public static function fromArray(array $itemArray): Item
    {
        return new Item(
            $itemArray['id'] ?? '<unknown>',
            $itemArray['content'] ?? '<unknown>',
            $itemArray['status'] ?? 'outstanding',
            $itemArray['due_date'],
            $itemArray['created_at'] ?? '<unknown>',
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getDueDate()
    {
        return $this->dueDate;
    }

    public function setContent(string $content): Item
    {
        $this->content = $content;
        return $this;
    }

    public function setStatus(string $status): Item
    {
        $this->status = $status;
        return $this;
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'content' => $this->getContent(),
            'status' => $this->getStatus(),
            'created_at' => $this->getCreatedAt(),
            'due_date' => $this->getDueDate()
        ];
    }
}
