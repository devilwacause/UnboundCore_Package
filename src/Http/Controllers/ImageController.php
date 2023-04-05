<?php

namespace Devilwacause\UnboundCore\Http\Controllers;

use Devilwacause\UnboundCore\Http\{Interfaces\ImageRepositoryInterface, Services\ImageService};

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ImageController extends BaseController
{
    private $imageRepository;

    /**
     * @param ImageRepositoryInterface $imageRepository
     */
    public function __construct(ImageRepositoryInterface $imageRepository, ImageService $imageService) {
        $this->imageRepository = $imageRepository;
        $this->imageService = $imageService;
    }

    /**
     * Return image from provider
     *
     * @param Request $request
     * @param $fileUUID
     * @return mixed
     */
    public function show(Request $request, $fileUUID) {
        return $this->imageService->show($request, $fileUUID);
    }

    /**
     * Get the database record for an image
     *
     * @param $fileUUID
     * @return mixed
     */
    public function get(Request $request, $fileUUID) {
        return $this->imageService->get($request, $fileUUID);
    }

    /**
     * Store image and create new database record
     * Requires "Accept : application/json" for validation purposes
     * Saves image to storage and adds to database
     *
     * @param Request $request
     * @return int
     */
    public function create(Request $request)  {
        return $this->imageService->create($request);
    }

    /**
     * Update the database data about the image
     * Requires "Accept : application/json" for validation purposes
     * Updates image record in the database
     *
     * @param Request $request
     * @return void
     */
    public function update(Request $request) {
       return $this->imageRepository->update($request);
    }

    /**
     * Change the image that is stored
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return mixed
     */
    public function change(Request $request) {
        return $this->imageRepository->change($request);
    }

    /**
     * Move image to another folder and update database record
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return mixed
     */
    public function move(Request $request) {
        return $this->imageRepository->move($request);
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
        return $this->imageRepository->copy($request);
    }

    /**
     * Removes image from storage and database.  Clears cached images
     * Requires "Accept : application/json" for validation purposes
     *
     * @param Request $request
     * @return mixed
     */
    public function remove(Request $request)  {
        return $this->imageRepository->remove($request);
    }
}