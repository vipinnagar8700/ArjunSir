<?php

namespace OTGS\Toolset\Common\Auryn;

interface ReflectionCache
{
    public function fetch($key);
    public function store($key, $data);
}
