<?php

define('DATABASE_PATH', getcwd() . '/todo.json');
const PREFIX = "TODO-";
const arrayStatus = ['new', 'in-progress', 'done', 'rejected'];

function main(string $command, array $arguments)
{
    match ($command) {
        "list" => list_items($arguments),
        "add" => add_item($arguments),
        "delete" => delete_item($arguments),
        "edit" => edit_item($arguments),
        "set-status" => set_status($arguments),
        'search' => search_items($arguments),
        default => print 'Command not supported' . PHP_EOL
    };
}

function search_items(array $arguments)
{
    if (count($arguments) < 1) {
        print "You didn't content to search." . PHP_EOL . PHP_EOL;
        return;
    }

    $content = array_shift($arguments);
    $items = get_items();

    $filteredItems = array_filter($items, fn($item) => strpos(strtolower($item['content']), $content));

    print_items($filteredItems);
}

function set_status(array $arguments)
{
    if (count($arguments) < 2) {
        print "You didn't provide enough arguments to edit content." . PHP_EOL . PHP_EOL;
        return;
    }
    $idToEdit = array_shift($arguments);
    $status = array_shift($arguments);

    if (!in_array($status, arrayStatus, true)) {
        print "Your status is not correct.\nEnter one of this: new, in-progress, done, rejected." .PHP_EOL.PHP_EOL;
        return;
    }

    $items = get_items();
    $edited = false;

    foreach ($items as $key => $value)
        if ($value['id'] === $idToEdit) {
            if ($value['status'] === 'outdated')
                die('You can\'t change [outdated] status.' . PHP_EOL . PHP_EOL);
            $edited = true;
            $items[$key]['status'] = $status;
            break;
        }

    if(!$edited)
        print "Your ID doesn't exist!" .PHP_EOL.PHP_EOL;
    else {
        save_to_file($items);
        print "Item $idToEdit was edited." . PHP_EOL . PHP_EOL;
    }
}

function edit_item(array $arguments)
{
    if (count($arguments) < 2) {
        print "You didn't provide enough arguments to edit content." . PHP_EOL . PHP_EOL;
        return;
    }
    $idToEdit = array_shift($arguments);
    $content = array_shift($arguments);

    $items = get_items();
    $edited = false;

    foreach ($items as $key => $value)
        if ($value['id'] === $idToEdit) {
            $edited = true;
            $items[$key]['content'] = $content;
            break;
        }

    if(!$edited)
        print "Your ID is incorrect" .PHP_EOL.PHP_EOL;
    else {
        save_to_file($items);
        print "Item $idToEdit was edited." . PHP_EOL . PHP_EOL;
    }
}

function delete_item(array $arguments)
{
    if (count($arguments) < 1) {
        print "You didn't provide item ID to delete." . PHP_EOL . PHP_EOL;
        return;
    }
    $idToDelete = array_shift($arguments);

    $items = get_items();
    $filteredItems = array_filter($items, fn($item) => $item['id'] !== $idToDelete);

    if (count($items) > count($filteredItems)) {
        save_to_file($filteredItems);
        print "Item $idToDelete was deleted" . PHP_EOL . PHP_EOL;
    } else {
        print "Nothing to delete" . PHP_EOL . PHP_EOL;
    }
}

function add_item(array $data)
{
    if (count($data) < 1) {
        print "You didn't provide any content to add." . PHP_EOL . PHP_EOL;
        return;
    }
    $items = get_items();
//  $
    if (count($items) !== 0) {
        $lastItems = $items[count($items) - 1];
        $lastId = (int)str_replace(PREFIX, "", $lastItems['id']);
    } else
        $lastId = 0;

    $item = [
        'id' => PREFIX . ($lastId + 1),
        'created_at' => date('d-M-Y H:i:s'),
        'content' => array_shift($data),
        'status' => 'new',
//        'due_date' => date('d-M-Y H:i:s', strtotime(array_shift($data)))
    ];

    $time = array_shift($data);

    if (!empty($time)) {
        $item['due_date'] = date('d-M-Y H:i:s', strtotime($time));

        if ($item['due_date'] < $item['created_at']) {
            print "You entered an invalid due-date" . PHP_EOL. PHP_EOL;
            return;
        }
    }

    $items[] = $item;

    save_to_file($items);
    print "Item $item[id] was added." . PHP_EOL . PHP_EOL;
}


function list_items($arguments)
{
    print "## Todo items ##" . PHP_EOL;

    $type = array_shift($arguments);

    if (!empty($type))
        $items = array_filter(get_items(), fn($item) => $item['status'] === $type);
    else
        $items = get_items();

    if (empty($items)) {
        print "Nothing here yet..." . PHP_EOL . PHP_EOL;
        return;
    }

    print_items($items);
}

function print_items($items)
{
    foreach ($items as $item) {
        $state = $item['status'] === 'done' ? 'X' : ' '; # ctr + w

        print " - [$state] $item[id] from $item[created_at]" . PHP_EOL;
        print "   Content  : $item[content]" . PHP_EOL;
        print "   Status   : $item[status]" . PHP_EOL;
        if (!empty($item['due_date']))
            print "   Due Date : $item[due_date]" . PHP_EOL . PHP_EOL;
        else
            print "\n";
    }
}

function get_items()
{
    if (!file_exists(DATABASE_PATH)) {
        save_to_file([]);
    }

    update_items();

    return json_decode(file_get_contents(DATABASE_PATH), true);
}

function update_items()
{
    $items =  json_decode(file_get_contents(DATABASE_PATH), true);

    foreach ($items as &$item)
        if ($item['status'] !== 'done' && !empty($item['due_date']) && $item['due_date'] < date('d-M-Y H:i:s'))
            $item['status'] = 'outdated';

    save_to_file($items);
}

function save_to_file(array $items)
{
    file_put_contents(DATABASE_PATH, json_encode(array_values($items), JSON_PRETTY_PRINT));
}
