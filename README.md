🥡 Xulieta Plugin Installer
===========================

This package is part of [codelicia/xulieta][1], and is not intended
to be used separately.

#### How to use it

`Xulieta` will automatically scan dependencies to see if there is 
any package that is providing default configurations.

If you want your plugin to take advantage of that functionality,
we expect you to provide some information on your `composer.json`
file, ie:

```json
{
  "extra": {
    "xulieta": {
      "parser": ["Malukenho\\QuoPrimumTempore\\JsonParser"],
      "validator": ["Malukenho\\QuoPrimumTempore\\JsonValidator"]
    }
  }
}
```

---
[1]: https://github.com/codelicia/xulieta


