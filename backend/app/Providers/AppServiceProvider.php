<?php

declare(strict_types=1);

namespace App\Providers;

use App\Constants\IssueConstants;
use App\Constants\ReportingConstants;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Domain\CreditScoring\Services\PdfGeneratorInterface;
use App\Domain\CropRecommendation\Actions\BuildAnalysisContextAction;
use App\Domain\CropRecommendation\Actions\BuildsAnalysisContext;
use App\Domain\CropRecommendation\Repositories\CropRecommendationRepositoryInterface;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceProfile;
use App\Domain\Insurance\Services\EnrollmentPackGeneratorInterface;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use App\Domain\Marketplace\Services\PaymentGatewayInterface;
use App\Domain\Marketplace\Services\SmsServiceInterface;
use App\Domain\Shared\Database\TransactionManagerInterface;
use App\Domain\Shared\Events\EventDispatcherInterface;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Infrastructure\Contact\Repositories\EloquentContactMessageReplyRepository;
use App\Infrastructure\Contact\Repositories\EloquentIssueTicketRepository;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Infrastructure\CropRecommendation\Repositories\EloquentCropRecommendationRepository;
use App\Infrastructure\Insurance\Services\EnrollmentPackGeneratorService;
use App\Infrastructure\Marketplace\Repositories\EloquentCropReferencePriceRepository;
use App\Infrastructure\Marketplace\Repositories\EloquentForwardContractRepository;
use App\Infrastructure\Marketplace\Repositories\EloquentPurchaseRepository;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use App\Infrastructure\Marketplace\Services\TxtFlowSmsService;
use App\Infrastructure\Services\PdfGeneratorService;
use App\Infrastructure\Shared\Database\LaravelTransactionManager;
use App\Infrastructure\Shared\Events\LaravelEventDispatcher;
use App\Infrastructure\Shared\Repositories\EloquentAdminActionLogRepository;
use App\Infrastructure\Shared\Repositories\EloquentContentReportRepository;
use App\Policies\AdminContentPolicy;
use App\Policies\AdminUserPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\ContentReportPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CreditScorePolicy;
use App\Policies\CropDemandOfferPolicy;
use App\Policies\CropDemandPolicy;
use App\Policies\CropRecommendationPolicy;
use App\Policies\FarmVerificationPolicy;
use App\Policies\ForwardContractPolicy;
use App\Policies\InsuranceClaimPolicy;
use App\Policies\InsuranceEnrollmentPolicy;
use App\Policies\InsuranceProfilePolicy;
use App\Policies\IssueTicketPolicy;
use App\Policies\PlotPolicy;
use App\Policies\UserAddressPolicy;
use Domain\Contact\Models\ContactMessage;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CropRecommendationRepositoryInterface::class, EloquentCropRecommendationRepository::class);
        $this->app->bind(BuildsAnalysisContext::class, BuildAnalysisContextAction::class);
        $this->app->bind(ForwardContractRepositoryInterface::class, EloquentForwardContractRepository::class);
        $this->app->bind(PurchaseRepositoryInterface::class, EloquentPurchaseRepository::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);

        $this->app->bind(CropReferencePriceRepositoryInterface::class, EloquentCropReferencePriceRepository::class);
        $this->app->bind(SmsServiceInterface::class, TxtFlowSmsService::class);

        $this->app->bind(PaymentGatewayInterface::class, PayMongoService::class);
        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        $this->app->bind(PdfGeneratorInterface::class, PdfGeneratorService::class);
        $this->app->bind(EnrollmentPackGeneratorInterface::class, EnrollmentPackGeneratorService::class);
        $this->app->bind(AdminActionLogRepositoryInterface::class, EloquentAdminActionLogRepository::class);
        $this->app->bind(ContactMessageReplyRepositoryInterface::class, EloquentContactMessageReplyRepository::class);
        $this->app->bind(ContentReportRepositoryInterface::class, EloquentContentReportRepository::class);
        $this->app->bind(IssueTicketRepositoryInterface::class, EloquentIssueTicketRepository::class);
    }

    public function boot(): void
    {
        // Prevent auth middleware from redirecting to 'login' route (API-only app)
        Authenticate::redirectUsing(fn () => null);

        // Customize the Email Verification URL to point to the frontend SPA
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $url = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return config('app.frontend_url', 'http://localhost:5173').'/auth/verify-email?verify_url='.urlencode($url);
        });

        // Customize the Password Reset URL to point to the frontend SPA
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return config('app.frontend_url', 'http://localhost:5173').'/auth/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // Register authorization policies
        Gate::policy(Plot::class, PlotPolicy::class);
        Gate::policy(CropRecommendation::class, CropRecommendationPolicy::class);
        Gate::policy(ForwardContract::class, ForwardContractPolicy::class);
        Gate::policy(UserAddress::class, UserAddressPolicy::class);
        Gate::policy(CropDemand::class, CropDemandPolicy::class);
        Gate::policy(CropDemandOffer::class, CropDemandOfferPolicy::class);
        Gate::policy(CreditScoreSnapshot::class, CreditScorePolicy::class);
        Gate::policy(User::class, AdminUserPolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
        Gate::policy(ContentReport::class, ContentReportPolicy::class);
        Gate::policy(IssueTicket::class, IssueTicketPolicy::class);
        Gate::policy(InsuranceClaim::class, InsuranceClaimPolicy::class);
        Gate::policy(InsuranceEnrollment::class, InsuranceEnrollmentPolicy::class);
        Gate::policy(InsuranceProfile::class, InsuranceProfilePolicy::class);
        Gate::define(ConversationPolicy::CREATE_ABILITY, [ConversationPolicy::class, 'create']);
        Gate::define(AdminContentPolicy::VIEW_ABILITY, [AdminContentPolicy::class, 'viewAny']);
        Gate::define(AdminContentPolicy::MODERATE_ABILITY, [AdminContentPolicy::class, 'moderate']);
        Gate::define(FarmVerificationPolicy::VIEW_ANY_ABILITY, [FarmVerificationPolicy::class, 'viewAny']);
        Gate::define(FarmVerificationPolicy::VIEW_ABILITY, [FarmVerificationPolicy::class, 'view']);
        Gate::define(FarmVerificationPolicy::DECIDE_ABILITY, [FarmVerificationPolicy::class, 'decide']);

        // One shared creation budget for the new and legacy report routes.
        RateLimiter::for(
            ReportingConstants::REPORT_MINUTE_LIMITER,
            fn (Request $request): Limit => Limit::perMinute(ReportingConstants::REPORTS_PER_MINUTE)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
        );
        RateLimiter::for(
            ReportingConstants::REPORT_DAILY_LIMITER,
            fn (Request $request): Limit => Limit::perDay(ReportingConstants::REPORTS_PER_DAY)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
        );

        // Member issue tickets share the same creation budget shape as reports.
        RateLimiter::for(
            IssueConstants::ISSUE_MINUTE_LIMITER,
            fn (Request $request): Limit => Limit::perMinute(IssueConstants::ISSUES_PER_MINUTE)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
        );
        RateLimiter::for(
            IssueConstants::ISSUE_DAILY_LIMITER,
            fn (Request $request): Limit => Limit::perDay(IssueConstants::ISSUES_PER_DAY)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
        );

        // Load channel definitions without auto-registering the legacy web broadcasting/auth route
        require base_path('routes/channels.php');

        // Log Viewer: nginx basic auth is the real gate in production; the app
        // verifies the request arrived authenticated via REMOTE_USER.
        LogViewer::auth(fn (Request $request) => $this->canViewLogs($request));
    }

    private function canViewLogs(Request $request): bool
    {
        if (! App::isProduction()) {
            return true;
        }

        $expectedUser = (string) config('log-viewer.basic_auth_user');

        if ($expectedUser === '') {
            return false;
        }

        // Only REMOTE_USER is trusted: nginx sets it after successful basic auth.
        // PHP_AUTH_USER is client-controlled (parsed from the Authorization
        // header without password verification) and must never grant access.
        $remoteUser = (string) ($request->server('REMOTE_USER') ?? '');

        return $remoteUser !== '' && hash_equals($expectedUser, $remoteUser);
    }
}
