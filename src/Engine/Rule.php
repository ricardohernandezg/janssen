<?php 

namespace Janssen\Engine;

abstract class Rule
{
 
    // PARAM TYPE
    const RULE_TYPE_ANY = 0;
    const RULE_TYPE_STRING = 1;
    const RULE_TYPE_INTEGER = 2;
    const RULE_TYPE_FLOAT = 3;
    const RULE_TYPE_BOOL = 4;
    const RULE_TYPE_DATE = 5;
    const RULE_TYPE_TIME = 6;
    const RULE_TYPE_DATETIME = 7;
    const RULE_TYPE_FILE = 8;
    const RULE_TYPE_ARRAY = 9;
    const RULE_TYPE_TIMEHM = 10;
    const RULE_TYPE_DECIMAL = 11;

    // RULES
    const RULE_REQUIRED = 41;
    const RULE_GRTN_0 = 42;
    const RULE_BETWEEN = 43;
    const RULE_MINLENGTH = 44;
    const RULE_MAXLENGTH = 45;
    const RULE_FIXED_LENGTH = 46;
    const RULE_FILE_MIMETYPE = 47;
    const RULE_FILE_MAXSIZE = 48;
    const RULE_FILE_MINSIZE = 55;
    const RULE_ONEOF = 49;
    const RULE_STRICT_STRING = 50;
    const RULE_EMAIL = 51;
    const RULE_ARRAY_MEMBER_TYPE = 52;
    const RULE_NUMBER_POSITIVE = 53;
    const RULE_NUMBER_NEGATIVE = 54;
    
}