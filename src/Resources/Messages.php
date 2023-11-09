<?php

namespace CrudApiRestfull\Resources;
use CrudApiRestfull\Enums\AbstractEnum;

final class Messages extends AbstractEnum
{
    public const NOT_FOUND_MESSAGE = "Resource not found.";
    public const UPDATED_SUCCESS_MESSAGE = "Resource udpated succesfully.";
    public const CREATED_SUCCESS_MESSAGE = "Resource created succesfully.";
    public const DATA_INVALID_MESSAGE = "The given data was invalid.";
    public const NOT_CONTENT_FOUND = "Resources not found.";
    public const SERVER_ERROR_MESSAGE = "Internal server error.";
    public const EXCEPTION_ERROR_MESSAGE = "Your request could not be processed, please contact the support team.";
    public const QUERY_ERROR_MESSAGE = "Your request could not be processed, please contact the support team.";
    public const TYPE_ERROR = "error";
    public const TYPE_SUCCESS = "success";
    public const DELETED_MESSAGE = "Resource deleted succesfully.";
    public const RESTORED_MESSAGE = "Resource restored succesfully.";
}
