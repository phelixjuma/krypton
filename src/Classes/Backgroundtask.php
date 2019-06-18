<?php

/**
 * This is script handles background tasks.
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

final class Backgroundtask {

    /**
     * This is the function to start the background task.
     * It executes a shell command for the provided task
     * @param string $task the task to execute
     * @param string $outputFile File to write the output of the process to; defaults to /dev/null
     * @param boolean $append set to true if output should be appended to $outputfile
     * @return int The process id (PID) of the task
     */
    public static function startTask($task, $outputFile = 'background.txt', $append = false) {

        $taskPID = (int) shell_exec(sprintf('%s %s %s 2>&1 & echo $!', $task, ($append) ? '>>' : '>', $outputFile));

        return $taskPID;
    }

    /**
     * function to check if a task exists and is running
     * @param int $taskPID the process id of the task
     * @return boolean
     */
    public static function isTaskRunning($taskPID) {
        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $taskPID));

            if (count(preg_split("/\n/", $result)) > 2 && !preg_match('/ERROR: Process ID out of range/', $result)) {
                return true;
            }
        } catch (Exception $e) {

        }
        return false;
    }

    /**
     * function to stop a task
     * @param int $taskPID the process id of the task
     * @return boolean
     */
    public static function stopTask($taskPID) {
        $response['error'] = 1;
        $response['data'] = null;

        try {
            $result = shell_exec(sprintf('kill %d 2>&1', $taskPID));

            if (!preg_match('/No such process/', $result)) {
                return true;
            }
        } catch (Exception $e) {

        }
        return false;
    }

}
