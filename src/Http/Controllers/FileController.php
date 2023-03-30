<?php

namespace Devilwacause\UnboundCore\Http\Controllers;


use Devilwacause\UnboundCore\Http\{
    Interfaces\FileRepositoryInterface,
};

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FileController extends BaseController
{
    private $fileRepository;

    /**
     * @param FileRepositoryInterface $fileRepository
     */
    public function __construct(FileRepositoryInterface $fileRepository) {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Return file to browser
     *
     * @param $fileUUID
     * @return mixed
     */
    public function show($fileUUID) {
        return $this->fileRepository->show($fileUUID);
    }

    /**
     * Get the database record for a file
     *
     * @param $fileUUID
     * @return mixed
     */
    public function get($fileUUID) {
        return $this->fileRepository->get($fileUUID);
    }

    /**
     * Return File as Download
     *
     * @param $fileUUID
     * @return void
     */
    public function download($fileUUID)  {
        return $this->fileRepository->download($fileUUID);
    }

    /**
     * Store file and create new database record
     * Requires "Accept : application/json" for validation purposes
     * Saves file to storage and adds to database
     *
     * @param Request $request
     * @return void
     */
    public function create(Request $request) {
        $file = $this->fileRepository->create($request);
    }

    /**
     * Update the database data about the file
     * Requires "Accept : application/json" for validation purposes
     * Updates file record in the database
     *
     * @param Request $request
     * @return mixed
     */
    public function update(Request $request) {
        return $this->fileRepository->update($request);
    }

    /**
     * Change the file that is stored
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return mixed
     */
    public function change(Request $request) {
        return $this->fileRepository->change($request);
    }

    /**
     * Move file to another folder and update database record
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return mixed
     */
    public function move(Request $request) {
        return $this->fileRepository->move($request);
    }

    /**
     * Copy file to another folder
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return mixed
     */
    public function copy(Request $request) {
        return $this->fileRepository->copy($request);
    }

    /**
     * Delete the file from the database and disk
     * Requires "Accept : application/json" for validation purposes
     *
     * @param Request $request
     * @return void
     */
    public function remove(Request $request) {
        return $this->fileRepository->remove($request);
    }
}