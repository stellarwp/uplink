/**
 * Application shell — header + 2-tab navigation.
 *
 * Tabs: My Products | Licenses
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Cloud } from 'lucide-react';
import { MyProductsTab } from '@/components/organisms/MyProductsTab';
import { LicenseList } from '@/components/organisms/LicenseList';
import { cn } from '@/lib/utils';

type Tab = 'my-products' | 'licenses';

/**
 * @since TBD
 */
export function AppShell() {
    const [ activeTab, setActiveTab ] = useState<Tab>( 'my-products' );
    const [ addLicenseOpen, setAddLicenseOpen ] = useState( false );

    // When MyProductsTab requests to open the Add License dialog,
    // switch to the Licenses tab which owns the dialog.
    const handleAddLicenseRequest = () => {
        setActiveTab( 'licenses' );
        // Small delay so tab content mounts first, then dialog can be triggered.
        // LicenseList manages its own dialog state; we signal via a key prop trick.
        setAddLicenseOpen( true );
    };

    return (
        <div className="max-w-[1200px] mx-auto p-4 md:p-8 space-y-6">
            {/* Page Header */}
            <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                    <Cloud className="w-6 h-6 text-primary" />
                </div>
                <div>
                    <h1 className="text-2xl font-semibold text-foreground m-0">
                        { __( 'Liquid Web Software', '%TEXTDOMAIN%' ) }
                    </h1>
                    <p className="text-sm text-muted-foreground m-0">
                        { __( 'Manage your product licenses and features', '%TEXTDOMAIN%' ) }
                    </p>
                </div>
            </div>

            {/* Tab Navigation */}
            <div className="border-b border-border">
                <nav className="flex gap-0" aria-label={ __( 'Dashboard tabs', '%TEXTDOMAIN%' ) }>
                    { (
                        [
                            { id: 'my-products', label: __( 'My Products', '%TEXTDOMAIN%' ) },
                            { id: 'licenses', label: __( 'Licenses', '%TEXTDOMAIN%' ) },
                        ] as const
                    ).map( ( tab ) => (
                        <button
                            key={ tab.id }
                            type="button"
                            role="tab"
                            aria-selected={ activeTab === tab.id }
                            onClick={ () => setActiveTab( tab.id ) }
                            className={ cn(
                                'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                                activeTab === tab.id
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
                            ) }
                        >
                            { tab.label }
                        </button>
                    ) ) }
                </nav>
            </div>

            {/* Tab Content */}
            <div role="tabpanel">
                { activeTab === 'my-products' && (
                    <MyProductsTab onAddLicense={ handleAddLicenseRequest } />
                ) }
                { activeTab === 'licenses' && (
                    <LicenseList openAddDialog={ addLicenseOpen } onAddDialogClose={ () => setAddLicenseOpen( false ) } />
                ) }
            </div>
        </div>
    );
}
