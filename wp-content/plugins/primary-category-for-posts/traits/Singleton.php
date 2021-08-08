<?php
/**
 * Trait Singleton
 * Trait restricts any class extended from this trait to have only one instance at most
 */
trait Singleton
{
    /**
     * Singleton constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Restricting use to clone an instance
     */
    final public function __clone()
    {
        //
    }

    /**
     * Create an instance of a class and cache it
     *
     * @return mixed
     */
    final public static function get_instance()
    {
        static $instances = [];

        $called_class = get_called_class();

        if (! isset($instances[$called_class]) ) {
            $instances[$called_class] = new $called_class();

            return $instances[$called_class];
        }

        return $instances[$called_class];
    }
}
