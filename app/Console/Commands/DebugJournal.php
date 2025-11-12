<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Journal;

class DebugJournal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:journal {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug journal posting issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $journalId = $this->argument('id');

        if ($journalId) {
            $journal = Journal::with('details.account')->find($journalId);
            if (!$journal) {
                $this->error("Journal with ID {$journalId} not found");
                return;
            }
            $this->debugSingleJournal($journal);
        } else {
            $journals = Journal::with('details')->orderBy('created_at', 'desc')->take(5)->get();
            $this->info("Last 5 journals:");
            foreach ($journals as $journal) {
                $this->debugSingleJournal($journal);
                $this->line("---");
            }
        }
    }

    private function debugSingleJournal($journal)
    {
        $this->info("Journal ID: {$journal->id}");
        $this->info("Reference: {$journal->reference}");
        $this->info("Status: {$journal->status}");
        $this->info("Description: {$journal->description}");
        $this->info("Details count: " . $journal->details->count());

        if ($journal->details->count() > 0) {
            $this->info("Total Debit: " . $journal->totalDebit);
            $this->info("Total Credit: " . $journal->totalCredit);
            $this->info("Is Balanced: " . ($journal->isBalanced ? 'Yes' : 'No'));
            $this->info("Can Be Posted: " . ($journal->canBePosted() ? 'Yes' : 'No'));

            $this->table(
                ['Account', 'Debit', 'Credit', 'Description'],
                $journal->details->map(function ($detail) {
                    return [
                        $detail->account->code . ' - ' . $detail->account->name,
                        number_format($detail->debit, 2),
                        number_format($detail->credit, 2),
                        $detail->description
                    ];
                })
            );
        } else {
            $this->error("No details found!");
        }
    }
}
