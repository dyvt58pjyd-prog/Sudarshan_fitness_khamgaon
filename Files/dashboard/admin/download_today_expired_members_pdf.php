<?php
/**
 * Sudarshan Fitness Khamgaon - Today's Expired Members List PDF Generator
 * 
 * Standalone direct endpoint that downloads only the members whose subscriptions
 * expired on the current date (today) and have not been renewed.
 * 
 * Supports:
 * - Direct download: download_today_expired_members_pdf.php
 * - Inline browser preview: download_today_expired_members_pdf.php?view=1
 * - Specific date query: download_today_expired_members_pdf.php?date=YYYY-MM-DD
 */

$_GET['filter'] = 'today';
require_once __DIR__ . '/download_expired_members_pdf.php';
