<?php

namespace Carteni\ToDo2;

class Application
{
    private array $items = [];
    private array $arrayStatus = ['new', 'in-progress', 'done', 'rejected'];

    public function __construct(
        protected string      $path,
        protected string      $prefix,
        protected ?Filesystem $fs = null,
        protected ?IO         $io = null,
        protected ?Error      $err = null
    )
    {
        $this->fs = $this->fs ?? new Filesystem();
        $this->io = $this->io ?? new IO();
        $this->err = $this->err ?? new Error();
    }

    public function run()
    {
        $this->loadItems();
        while ($cmd = readline("todo>")) {
            $this->updateItems();

            try {
                match ($cmd) {
                    "list" => $this->listItems(),
                    'help' => $this->help(),
                    "add" => $this->addItem(readline("new item> "), readline("due-date> ")),
                    "delete" => $this->deleteItem(readline("item to delete> ")),
                    "edit-item" => $this->editItem(readline("item ID> "), readline("item new content> ")),
                    "set-status" => $this->setStatus(readline("item ID> "), readline("item new status> ")),
                    "search-items" => $this->searchItems(readline("content to search> ")),
                    default => $this->io->printLine("Command $cmd not supported")
                };
            } catch (\Throwable $e) {
                $this->io->printLine($e->getMessage());
                $this->saveItems($this->items);
            }
        }
        $this->saveItems($this->items);
    }

    public function help()
    {
        $this->io
            ->printLine("Available commands: list, add, delete, set-status, edit-item, search_items, help")
            ->printLine();
    }

    public function deleteItem(string $idToDelete): void
    {
        if (empty($idToDelete)) {
            $this->err->throwError("You didn't provide item ID to delete.");
        }

        $filteredItems = array_filter($this->items, fn(Item $item) => $item->getId() !== $idToDelete);

        if (count($this->items) > count($filteredItems)) {
            $this->io
                ->printLine("Item $idToDelete was deleted")
                ->printLine();
        } else {
            $this->io
                ->printLine("Nothing to delete")
                ->printLine();
        }

        $this->items = $filteredItems;
    }

    public function addItem(string $content, string $dueDate): Item
    {
        if (empty($content)) {
            $this->err->throwError("You didn't provide item content.");
        }

        $lastId = 0;

        if (count($this->items) > 0) {
            $lastItems = $this->items[count($this->items) - 1];
            $lastId = (int)str_replace($this->prefix, "", $lastItems->getId());
        }

        $item = new Item(
            true,
            $this->prefix . ($lastId + 1),
            $content,
            'new',
            $dueDate === "" ? null : \DateTime::createFromFormat("d-m-Y H:i:s", $dueDate),
            null
        );

        $this->items[] = $item;

        $this->io
            ->printLine("Item {$item->getId()} was added.")
            ->printLine();

        return $item;
    }


    public function listItems(): void
    {
        $this->io
            ->printLine("## Todo items ##");

        if (empty($this->items)) {
            $this->io
                ->printLine("Nothing here yet...")
                ->printLine();
            return;
        }

        foreach ($this->items as $item) {
            $this->printItem($item);
        }
    }

    public function printItem(Item $item): void
    {
        $state = $item->isDone() ? 'X' : ' ';
        $this->io
            ->printLine(" - [$state] " . (gettype($item->getId()) !== 'boolean' && $item->getId() !== null ? $item->getId() : "<unknown>") . " from " .
                (gettype($item->getCreatedAt()) !== 'boolean' ? $item->getCreatedAt()?->format("d-M-Y H:i:s") : "<unknown"));
        $this->io
            ->printLine("   Content  : " . (gettype($item->getContent()) !== 'boolean' ? $item->getContent() : "unknown>"));
        $this->io
            ->printLine("   Status   : " . (gettype($item->getStatus()) !== 'boolean' ? $item->getStatus() : "<unknown>"));
        (gettype($item->getDueDate()) !== 'boolean' && $item->getDueDate() !== null &&
            $this->io
                ->printLine("   Due Date  : {$item->getDueDate()->format("d-M-Y H:i:s")}"));

        $this->io
            ->printLine();
    }

    public function loadItems(): void
    {
        if (!$this->fs->exists($this->path)) {
            $this->saveItems($this->items);
        }

        $arrayOfItems = json_decode($this->fs->get($this->path), true);
        $this->items = array_map(fn($item) => Item::fromArray($item), $arrayOfItems);
    }


    public function getItems(): array
    {
        return $this->items;
    }

    public function saveItems(array $items): void
    {
        $itemsArray = array_map(fn(Item $item) => $item->toArray(), $items);
        $this->fs->put($this->path, json_encode(array_values($itemsArray), JSON_PRETTY_PRINT));
    }

    public function editItem(string $idToEdit, string $newContent): void
    {
        $edited = false;

        if (!$newContent) {
            $this->err->throwError("You didn't provide item content.");
        }

        foreach ($this->items as $item) {
            if ($item->getId() === $idToEdit) {
                $edited = true;
                $item->setContent($newContent);
                break;
            }
        }

        if (!$edited) {
            $this->err->throwError("Your ID is not correct.");
        } else {
            $this->io
                ->printLine("Item $idToEdit was edited")
                ->printLine();
        }
    }

    public function setStatus(string $idToEdit, string $newStatus): void
    {
        if (!in_array($newStatus, $this->arrayStatus, true)) {
            $this->err->throwError("Your status is not correct.\nEnter one of this: new, in-progress, done, rejected." . PHP_EOL . PHP_EOL);
        }

        $edited = false;

        foreach ($this->items as $item) {
            if ($item->getId() === $idToEdit) {
                if ($item->getStatus() === 'outdated') {
                    $this->err->throwError("You can't change [outdated] status");
                }
                $edited = true;
                $item->setStatus($newStatus);
                break;
            }
        }

        if (!$edited) {
            $this->err->throwError("Your ID is not correct.");
        } else {
            $this->io
                ->printLine("Item's status of item $idToEdit was updated")
                ->printLine();
        }
    }

    public function updateItems(): void
    {
        foreach ($this->items as $item) {
            if ($item->getStatus() !== 'done' && (!empty($item->getDueDate())) && $item->getDueDate() < date('d-M-Y H:i:s')) {
                $item->setStatus('outdated');
            }
        }
    }

    private function searchItems(string $searchedContent): void
    {
        $filteredItems = array_filter($this->items, fn($item) => strpos(strtolower($item->getContent()), $searchedContent));

        if (!$filteredItems) {
            $this->err->throwError("This content doesn't exist.");
        }

        foreach ($filteredItems as $item) {
            $this->printItem($item);
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
