---
layout: default
title: Welcome
nav_order: 1
redirect_from:
  - /docs/2.5/
---

# Doctrine Enhancement Pack

[![Build Status](https://travis-ci.org/darkwebdesign/doctrine-enhancement-pack.svg?branch=2.6)](https://travis-ci.org/darkwebdesign/doctrine-enhancement-pack?branch=2.6)
[![Coverage Status](https://codecov.io/gh/darkwebdesign/doctrine-enhancement-pack/branch/2.6/graph/badge.svg)](https://codecov.io/gh/darkwebdesign/doctrine-enhancement-pack)
[![PHP Version](https://img.shields.io/badge/php-7.1%2B-777BB3.svg)](https://php.net/)
[![Doctrine Version](https://img.shields.io/badge/doctrine-2.6-2E6BC8.svg)](http://www.doctrine-project.org/)
[![License](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/license?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)

Doctrine Enhancement Pack is a collection of extra Doctrine enhancements that you can use in your Doctrine applications.

## Features

### Enhanced Events

* Access to the original entity in the `pre-update` and `post-update` lifecycle events
* Access to the created, updated (also their original entities) and deleted entities in the `on-flush` and `post-flush`
  events
* Modifying the actual entities (instead of via "change set" array) in the `on-flush` event and `pre-update` lifecycle
  event
* Automatic recomputing of the "change set" after modifying the entities in the `on-flush` event

## License

Doctrine Enhancement Pack is licensed under the MIT License - see the `LICENSE` file for details.
