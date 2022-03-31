<?php

namespace Tests;

use Carteni\ToDo2\Application;
use Carteni\ToDo2\Error;
use Carteni\ToDo2\Filesystem;
use Carteni\ToDo2\IO;
use Carteni\ToDo2\Item;
use PHPUnit\Framework\TestCase;

class ToDoTest extends TestCase
{
    /** @dataProvider ItemsArrayProvider */
    public function testGetItems(array $itemsArray)
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->expects(self::once())->method('exists')->willReturn(true);
        $fs->expects(self::once())->method('get')->willReturn(json_encode($itemsArray));

        $app = new Application(__DIR__ . "/../../todo.json", "TODO-", $fs);
        $app->loadItems();
        $items = $app->getItems();

        self::assertSameSize($itemsArray, $items);
        self::assertContainsOnlyInstancesOf(Item::class, $items);
    }

    /** @dataProvider ItemsArrayProvider */
    public function testSaveItems(array $itemsArray)
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('put')->willReturnCallback(function (string $path, string $content) use ($itemsArray) {

            self::assertEquals(json_encode($itemsArray, JSON_PRETTY_PRINT), $content);
            return true;

        });

        $app = new Application(__DIR__ . "/../../todo.json", "TODO-", $fs);
        $objectItems = array_map([Item::class, 'fromArray'], $itemsArray);
        $app->saveItems($objectItems);
    }

    public function testHelp(){
        $io = $this->createMock(IO::class);
        $io->expects(self::exactly(2))->method('printLine')->willReturnSelf();

        $app = new Application(__DIR__ . "/../../todo.json", "TODO-", null, $io);
        $app->help();
    }

    /** @dataProvider ItemsArrayProvider */
    public function testDeleteItemSuccessfully(array $itemsArray)
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);
        $fs->method('get')->willReturn(json_encode($itemsArray));

        $io = $this->createMock(IO::class);
        $io->expects(self::exactly(2))->method('printLine')->willReturnSelf();

        $app = new Application(__DIR__ . "/../../todo.json", "TODO-", $fs, $io);
        $app->loadItems();
        $app->deleteItem($itemsArray[0]['id']); //success
//        $app->deleteItem("noId"); //error
        $finalItems = $app->getItems();

        self::assertCount(count($itemsArray) - 1, $finalItems);
    }

    /** @dataProvider ItemsArrayProvider */
    public function testDeleteItemThrowingError(array $itemsArray)
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);
        $fs->method('get')->willReturn(json_encode($itemsArray));

        $io = $this->createMock(IO::class);
        $io->expects(self::exactly(0))->method('printLine')->willReturnSelf();

        $err = $this->createMock(Error::class);
        $err->expects(self::once())->method('throwError');

        $app = new Application(__DIR__ . "/../../todo.json", "TODO-", $fs, $io, new Error());
        $app->loadItems();
        $app->deleteItem("");
        $finalItems = $app->getItems();

        self::assertSameSize($itemsArray, $finalItems);
    }

    /** @dataProvider ItemsArrayProvider */
    public function testAddItemSuccessfully(array $itemsArray)
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->expects(self::once())->method('exists')->willReturn(true);
        $fs->expects(self::once())->method('get')->willReturn(json_encode($itemsArray));

        $io = $this->createMock(IO::class);
        $io->expects(self::exactly(2))->method('printLine')->willReturnSelf();

        $app = new Application(__DIR__ . "/../../todo.json", "TODO-", $fs, $io);
        $app->loadItems();
        $app->addItem("New Test Item", ""); //success
//        $app->addItem("New Test Item", "31-3-2022 19:23:15"); //success
//        $app->addItem("", ""); // unsuccessful
//        $app->addItem("", "31-3-2022 18:41:11"); // unsuccessful

    }



    public function ItemsArrayProvider(): array
    {
        $item1 = ['id' => 'TODO-1', 'content' => 'First Item', 'status' => 'new', 'due_date' => null];
        $item2 = ['id' => 'TODO-2', 'content' => 'Second Item With Timer', 'status' => 'new', 'due_date' => null];

        return [
            [ //First case
                [$item1, $item2]
            ],
            [ //Second case
                [$item1]
            ]
        ];
    }


}