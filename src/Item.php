<?php

namespace Carteni\ToDo2;

class Item
{
    public function __construct(
        protected bool                $new,
        protected null|bool|string    $id = false,
        protected null|bool|string    $content = false,
        protected null|bool|string    $status = false,
        protected null|bool|\DateTime $dueDate = false,
        protected null|bool|\DateTime $createdAt = false
    )
    {
        $this->new && $this->createdAt = $this->createdAt ?? \DateTime::createFromFormat("d-m-Y H:i:s", date("d-m-Y H:i:s"));
    }

    public
    static function fromArray(array $itemArray): Item
    {
        $arrayKeys = array_keys($itemArray);

        $id = false;
        $content = false;
        $status = false;
        $dueDate = false;
        $createdAt = false;

        in_array('id', $arrayKeys) && $id = $itemArray['id'];
        in_array('content', $arrayKeys) && $content = $itemArray['content'];
        in_array('status', $arrayKeys) && $status = $itemArray['status'];
        in_array('due_date', $arrayKeys) && $dueDate = $itemArray['due_date'];
        in_array('created_at', $arrayKeys) && $createdAt = $itemArray['created_at'];

        return new Item(
            false,
            $id,
            $content,
            $status,
            $dueDate !== false && $dueDate !== null ? \DateTime::createFromFormat("d-m-Y H:i:s", $dueDate) :
                (($dueDate === null) ? null : false),
            $createdAt !== false && $createdAt !== null ? \DateTime::createFromFormat("d-m-Y H:i:s", $createdAt) :
                (($createdAt === null) ? null : false)
        );
    }

    public
    function getId(): null|bool|string
    {
        return $this->id;
    }

    public
    function getContent(): null|bool|string
    {
        return $this->content;
    }

    public
    function getStatus(): null|bool|string
    {
        return $this->status;
    }

    public
    function getCreatedAt(): null|bool|\DateTime
    {
        return $this->createdAt;
    }

    public
    function getDueDate(): null|bool|\DateTime
    {
        return $this->dueDate;
    }

    public
    function setContent(string $content): Item
    {
        $this->content = $content;
        return $this;
    }

    public
    function setStatus(string $status): Item
    {
        $this->status = $status;
        return $this;
    }

    public
    function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function toArray(): array
    {
        $item = [];

        gettype($this->getId()) !== 'boolean' && $item['id'] = $this->getId();
        gettype($this->getContent()) !== 'boolean' && $item['content'] = $this->getContent();
        gettype($this->getStatus()) !== 'boolean' && $item['status'] = $this->getStatus();
        gettype($this->getDueDate()) !== 'boolean' && $item['due_date'] = $this->getDueDate()?->format("d-m-Y H:i:s");
        gettype($this->getCreatedAt()) !== 'boolean' && $item['created_at'] = $this->getCreatedAt()?->format("d-m-Y H:i:s");

        return $item;
    }
}
