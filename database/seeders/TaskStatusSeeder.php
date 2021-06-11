<?php

namespace Database\Seeders;

use App\Models\Ref\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $task_status = new TaskStatus();
        $task_status->desc = 'Tugasan Diberi';
        $task_status->class = 'badge bg-info';
        $task_status->save();

        $task_status = new TaskStatus();
        $task_status->desc = 'Sedang Disemak';
        $task_status->class = 'badge bg-primary';
        $task_status->save();

        $task_status = new TaskStatus();
        $task_status->desc = 'Berjaya Diselesaikan';
        $task_status->class = 'badge bg-success';
        $task_status->save();

        $task_status = new TaskStatus();
        $task_status->desc = 'Gagal Diselesaikan';
        $task_status->class = 'badge bg-danger';
        $task_status->save();
    }
}
