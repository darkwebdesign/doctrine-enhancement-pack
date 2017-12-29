# Doctrine Enhanced Events

[![Latest Stable Version](https://poser.pugx.org/darkwebdesign/doctrine-enhanced-events/v/stable?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhanced-events)
[![Total Downloads](https://poser.pugx.org/darkwebdesign/doctrine-enhanced-events/downloads?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhanced-events)
[![License](https://poser.pugx.org/darkwebdesign/doctrine-enhanced-events/license?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhanced-events)

[![Build Status](https://travis-ci.org/darkwebdesign/doctrine-enhanced-events.svg?branch=2.4)](https://travis-ci.org/darkwebdesign/doctrine-enhanced-events?branch=2.4)
[![Coverage Status](https://codecov.io/gh/darkwebdesign/doctrine-enhanced-events/branch/2.4/graph/badge.svg)](https://codecov.io/gh/darkwebdesign/doctrine-enhanced-events)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.3-777BB3.svg)](https://php.net/)
[![Minimum Symfony Version](https://img.shields.io/badge/doctrine-%3E%3D%202.4-2E6BC8.svg)](http://www.doctrine-project.org/)

Doctrine Enhanced Events offers enhanced versions of the original Doctrine (lifecycle) events.

Learn more about it in its [documentation](https://github.com/darkwebdesign/symfony-addon-pack/blob/2.4/doc/reference/constraints/index.md).

## Features

* Access to the original entity in the `pre-update` and `post-update` lifecycle event.
* Modifying via updated entity itself (instead of via "change set" array) in the `pre-update` lifecycle event.
* Modifications to the updated entity in the `pre-update` lifecycle event automatically triggers recomputing of "change set".

## Installing via Composer

```bash
composer require darkwebdesign/doctrine-enhanced-events
```

```bash
composer install
```

## License

Doctrine Enhanced Events is licensed under the MIT License - see the `LICENSE` file for details.
