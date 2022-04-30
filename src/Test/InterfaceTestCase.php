<?php
namespace CrudApiRestfull\Test;

interface InterfaceTestCase
{
    public function test_url_correct();
    public function test_url_validate();
    public function test_model_can_be_listed();
    public function test_model_can_be_created();
    public function test_model_can_be_retrived();
    public function test_model_can_be_updated();
    public function test_model_can_be_deleted();
    public function test_resource_return_not_found();
    public function test_model_required_validation();
    public function test_model_unique_validation();
    public function test_model_exists_validation();
}