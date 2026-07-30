<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $normal_balance
 * @property string $report_category
 * @property string|null $cash_flow_category
 * @property int|null $parent_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $children
 * @property-read int|null $children_count
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $journalEntryLines
 * @property-read int|null $journal_entry_lines_count
 * @property-read Account|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account forReport(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account labaRugi()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account neraca()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCashFlowCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereNormalBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereReportCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withoutTrashed()
 * @mixin \Eloquent
 */
	class Account extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $table_name
 * @property int $record_id
 * @property string $action
 * @property array<array-key, mixed>|null $old_data
 * @property array<array-key, mixed>|null $new_data
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail byAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail forRecord(string $tableName, int $recordId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail forTable(string $tableName)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereNewData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereOldData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereTableName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereUserId($value)
 * @mixin \Eloquent
 */
	class AuditTrail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $description
 * @property numeric $amount
 * @property string $bank_source
 * @property string $source_type
 * @property string $mutation_type
 * @property string|null $matched_invoice_ref
 * @property array<array-key, mixed>|null $matched_invoice_data
 * @property int|null $journal_entry_id
 * @property string $status
 * @property int|null $uploaded_by
 * @property int|null $matched_by
 * @property int|null $completed_by
 * @property int|null $submitted_by
 * @property int|null $posted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $completer
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\User|null $matcher
 * @property-read \App\Models\User|null $poster
 * @property-read \App\Models\User|null $submitter
 * @property-read \App\Models\User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereBankSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMatchedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMatchedInvoiceData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMatchedInvoiceRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMutationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation wherePostedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation withoutTrashed()
 * @mixin \Eloquent
 */
	class BankMutation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property int|null $closed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $closedBy
 * @property-read bool $is_open
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod closed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod open()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class FiscalPeriod extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $entry_date
 * @property string|null $reference
 * @property string $description
 * @property int $fiscal_period_id
 * @property int $created_by
 * @property bool $is_closing
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $posted_by
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property int|null $submitted_by
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\FiscalPeriod $fiscalPeriod
 * @property-read bool $is_balanced
 * @property-read float $total_credit
 * @property-read float $total_debit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $lines
 * @property-read int|null $lines_count
 * @property-read \App\Models\User|null $postedBy
 * @property-read \App\Models\User|null $submittedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry closing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry forPeriod(int $fiscalPeriodId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry posted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry regular()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereEntryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereFiscalPeriodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereIsClosing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry wherePostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry wherePostedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry withoutTrashed()
 * @mixin \Eloquent
 */
	class JournalEntry extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property numeric $debit
 * @property numeric $credit
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class JournalEntryLine extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser {}
}

