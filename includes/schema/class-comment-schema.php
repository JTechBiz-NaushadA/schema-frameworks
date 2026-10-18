<?php
namespace SchemaFrameworks\Schema;
if ( ! defined( 'ABSPATH' ) ) { exit; }
use SchemaFrameworks\Config;
use SchemaFrameworks\Schema_Manager;

class Comment_Schema implements Schema_Interface {
    private $config;
    private $manager;
    public function __construct( Config $config, Schema_Manager $manager ) {
        $this->config = $config;
        $this->manager = $manager;
    }
    public function get_id() { return 'comment'; }
    public function is_needed( $context = null ) { return false; }
    public function generate( $context = null ) { return array(); }
}