<?php

namespace {
    $pretend = [
        'functionsNotExisting' => [],
        'functionsExisting' => [],
        'extensionsNotExisting' => [],
        'extensionsExisting' => [],
        'classesNotExisting' => [],
        'classesExisting' => []
    ];
    $hasDeclaredMockFunctions = false;

    function pretendFunctionNotExisting($funcName)
    {
        global $pretend;
        $pretend['functionsNotExisting'][] = $funcName;
    }

    function pretendClassNotExisting($className)
    {
        global $pretend;
        $pretend['classesNotExisting'][] = $className;
    }

    function resetPretending()
    {
        global $pretend;
        $pretend = [
            'functionsNotExisting' => [],
            'functionsExisting' => [],
            'extensionsNotExisting' => [],
            'extensionsExisting' => [],
            'classesNotExisting' => [],
            'classesExisting' => []
        ];

    }
}

namespace DOMUtilForWebP {


    global $hasDeclaredMockFunctions;

    if(!$hasDeclaredMockFunctions)  {
        $hasDeclaredMockFunctions = true;
        function function_exists($function) {

            global $pretend;
            if (in_array($function, $pretend['functionsNotExisting'])) {
                return false;
            }
            if (in_array($function, $pretend['functionsExisting'])) {
                return true;
            }
            return \function_exists($function);
        }

        function extension_loaded($extension) {
            global $pretend;
            if (in_array($extension, $pretend['extensionsNotExisting'])) {
                return false;
            }
            if (in_array($extension, $pretend['extensionsExisting'])) {
                return true;
            }
            return \extension_loaded($extension);
        }

        function class_exists($className) {
            global $pretend;
            if (in_array($className, $pretend['classesNotExisting'])) {
                return false;
            }
            if (in_array($className, $pretend['classesExisting'])) {
                return true;
            }
            return \class_exists($className);
        }
    }
}
