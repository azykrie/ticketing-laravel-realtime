<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('How to Use')] class extends Component {
    public function isStaff(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'agent']);
    }
};
?>

<div class="max-w-3xl">
    <flux:heading size="xl" level="1">How to Use</flux:heading>
    <flux:text class="mt-1">A quick guide to using the ticketing system.</flux:text>

    <flux:separator variant="subtle" class="my-6" />

    <flux:heading size="lg" class="mb-2">For everyone</flux:heading>

    <flux:accordion>
        <flux:accordion.item heading="How do I create a ticket?">
            <flux:text>
                Open <strong>Tickets</strong>, click <strong>New Ticket</strong>, then fill in the title,
                category, priority, and description. You can attach screenshots or documents
                (max 5 files, 5 MB each). Click <strong>Submit Ticket</strong>.
            </flux:text>
        </flux:accordion.item>

        <flux:accordion.item heading="How do I follow up on my ticket?">
            <flux:text>
                Open the ticket from the <strong>Tickets</strong> list. Replies appear in real time,
                and you get a notification (bell icon) whenever someone replies or the status changes.
            </flux:text>
        </flux:accordion.item>

        <flux:accordion.item heading="What do the statuses mean?">
            <ul class="list-disc space-y-1 ps-5 text-sm text-zinc-600 dark:text-zinc-400">
                <li><strong>Open</strong>: the ticket was received and is waiting to be handled.</li>
                <li><strong>In Progress</strong>: a team member is working on it.</li>
                <li><strong>Resolved</strong>: a solution has been provided.</li>
                <li><strong>Closed</strong>: the ticket is finished and can no longer be replied to.</li>
            </ul>
        </flux:accordion.item>

        <flux:accordion.item heading="Can I edit or close my ticket?">
            <flux:text>
                You can edit the title and description of your own ticket, and close it once your problem
                is solved. After a ticket is closed, only the support team can reopen it.
            </flux:text>
        </flux:accordion.item>

        <flux:accordion.item heading="How do I attach or remove a file?">
            <flux:text>
                Choose files when creating a ticket or writing a reply. To remove a file you uploaded,
                click the red <strong>×</strong> on the attachment (available while the ticket is not closed).
            </flux:text>
        </flux:accordion.item>
    </flux:accordion>

    @if ($this->isStaff())
        <flux:heading size="lg" class="mb-2 mt-8">For support team (agent and admin)</flux:heading>

        <flux:accordion>
            <flux:accordion.item heading="How do I handle a ticket?">
                <flux:text>
                    Open the ticket and write a reply. The first staff reply on an open ticket automatically
                    sets it to <strong>In Progress</strong> and assigns it to you. Use <strong>Edit</strong>
                    in the ticket list to change status, priority, category, or assignee.
                </flux:text>
            </flux:accordion.item>

            <flux:accordion.item heading="How do I close or reopen a ticket?">
                <flux:text>
                    Use <strong>Close ticket</strong> on the ticket page once the problem is solved.
                    A closed ticket can be reopened with the <strong>Reopen</strong> button.
                </flux:text>
            </flux:accordion.item>

            <flux:accordion.item heading="How do I filter and search tickets?">
                <flux:text>
                    On the Tickets page, use the search box (title or code) and the status, priority,
                    and category filters. Click a column header to sort.
                </flux:text>
            </flux:accordion.item>
        </flux:accordion>
    @endif

    @role('admin')
        <flux:heading size="lg" class="mb-2 mt-8">For administrators</flux:heading>

        <flux:accordion>
            <flux:accordion.item heading="How do I manage users and roles?">
                <flux:text>
                    Open <strong>Users</strong> to add, edit, or delete accounts and change their role
                    (admin, agent, or user). You cannot delete or demote your own account, and users who
                    already have tickets cannot be deleted.
                </flux:text>
            </flux:accordion.item>

            <flux:accordion.item heading="How do I manage categories?">
                <flux:text>
                    Open <strong>Categories</strong> to add, rename, or delete categories.
                </flux:text>
            </flux:accordion.item>

            <flux:accordion.item heading="Who can delete tickets?">
                <flux:text>
                    Only administrators can delete a ticket. Deleting a ticket also removes its replies
                    and attachments permanently.
                </flux:text>
            </flux:accordion.item>
        </flux:accordion>
    @endrole
</div>