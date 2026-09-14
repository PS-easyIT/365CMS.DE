# 365CMS – Projektdokumentation | Abschnitt: Asset – AI Platform Changelog
> **Stand:** 2026-09-14 | **Version:** 3.4.00 | **Status:** Historical asset record | **Update:** 2026-09-14

## English

The following history belongs to the AI platform asset shipped under `CMS/assets/ai-platform/`. It is retained as an upstream asset record; current CMS integration is documented in the application services.

## Deutsch

Die folgende Historie gehört zum unter `CMS/assets/ai-platform/` ausgelieferten AI-Platform-Asset. Sie bleibt als Upstream-Assetnachweis erhalten; die aktuelle CMS-Integration ist in den Anwendungs-Services dokumentiert.

## Changelog
=========

0.6
---

* [BC BREAK] Change `Symfony\AI\Platform\Contract\JsonSchema\Factory` constructor signature in order to make schema generation extensible

0.4
---

 * Add thinking support to `AssistantMessage`
 * Add support for object serialization in template variables via `template_vars` option
 * Add support for populating existing object instances in structured output via `response_format` option

0.3
---

 * Add `StreamListenerInterface` to hook into response streams
 * [BC BREAK] Change `TokenUsageAggregation::__construct()` from variadic to array
 * Add `TokenUsageAggregation::add()` method to add more token usages
 * [BC BREAK] `CachedPlatform` has been renamed `CachePlatform` and moved as a bridge, please require `symfony/ai-cache-platform` and use `Symfony\AI\Platform\Bridge\Cache\CachePlatform`
 * [BC BREAK] `Metadata::merge()` method signature has changed to accept `Metadata` instead of array
 * [BC BREAK] Behavior of `Metadata::add()` has changed to merge existing keys instead of overwriting them
 * [BC BREAK] Move `Symfony\AI\Platform\Serializer\StructuredOutputSerializer` to `Symfony\AI\Platform\StructuredOutput\Serializer`

0.2
---

 * [BC BREAK] Change `ChoiceResult::__construct()` from variadic to accept array of `ResultInterface`

0.1
---

 * Add the component
