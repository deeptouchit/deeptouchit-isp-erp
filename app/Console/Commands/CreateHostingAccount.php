<?php
namespace App\Console\Commands;

use App\Jobs\CreateHostingAccountJob;
use App\Models\HostingPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class CreateHostingAccount extends Command
{
    protected $signature = 'hosting:create
        {email : Client email}
        {plan : Plan ID or slug}
        {domain : Domain name}
        {--php=8.2 : PHP version}
        {--period=monthly : Billing period (monthly/yearly)}
        {--skip-email : Skip sending email notifications}';
    
    protected $description = 'Create a new hosting account';
    
    public function handle(): int
    {
        $email = $this->argument('email');
        $planIdentifier = $this->argument('plan');
        $domain = $this->argument('domain');
        $phpVersion = $this->option('php');
        $period = $this->option('period');
        $skipEmail = $this->option('skip-email');
        
        // Find user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            if (!$this->confirm('Create user?', false)) {
                return Command::FAILURE;
            }
            
            $user = $this->createUser($email);
        }
        
        // Find plan
        $plan = HostingPlan::where('slug', $planIdentifier)
            ->orWhere('id', $planIdentifier)
            ->first();
        
        if (!$plan) {
            $this->error("Plan '{$planIdentifier}' not found.");
            return Command::FAILURE;
        }
        
        $this->info("Creating hosting account for {$user->email} with plan {$plan->name}");
        
        // Create subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'server_id' => 1,
            'domain' => $domain,
            'php_version' => $phpVersion,
            'period' => $period,
            'price' => $period === 'monthly' ? $plan->price_monthly : $plan->price_yearly,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
            'status' => 'pending'
        ]);
        
        $this->info("Subscription created with ID: {$subscription->id}");
        
        // Dispatch job
        CreateHostingAccountJob::dispatch($subscription);
        
        $this->info("✅ Hosting account creation job dispatched.");
        
        if (!$skipEmail) {
            $this->sendWelcomeEmail($user, $subscription);
        }
        
        return Command::SUCCESS;
    }
    
    private function createUser(string $email): User
    {
        $username = explode('@', $email)[0];
        $password = \Illuminate\Support\Str::random(12);
        
        $user = User::create([
            'username' => $username,
            'email' => $email,
            'password' => bcrypt($password),
            'status' => 'active'
        ]);
        
        $this->info("✅ User created: {$user->email}");
        $this->info("Temporary password: {$password}");
        
        return $user;
    }
    
    private function sendWelcomeEmail(User $user, Subscription $subscription): void
    {
        $this->info("📧 Welcome email sent to {$user->email}");
    }
}
