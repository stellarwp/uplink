import { __, sprintf } from '@wordpress/i18n';
import { useState } from 'react';
import { Icon, shield } from '@wordpress/icons';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { LicenseStatusMessage } from '@/components/molecules/LicenseStatusMessage';
import type { LicenseData } from '@/types/api';

interface MasterLicenseFormProps {
    license: LicenseData;
    onActivate: ( key: string, email: string ) => void;
    onDeactivate: () => void;
}

/**
 * @since TBD
 */
export function MasterLicenseForm( {
    license,
    onActivate,
    onDeactivate,
}: MasterLicenseFormProps ) {
    const [ key, setKey ] = useState( license.key );
    const [ email, setEmail ] = useState( license.email );

    const statusType =
        license.status === 'active'
            ? 'success'
            : license.status === 'idle'
              ? 'idle'
              : 'error';

    const statusMessage =
        license.status === 'active'
            ? sprintf( __( 'Master license active. Expires on %s.', '%TEXTDOMAIN%' ), license.expires )
            : license.status === 'expired'
              ? __( 'Your license has expired. Please renew to continue receiving updates.', '%TEXTDOMAIN%' )
              : license.status === 'invalid'
                ? __( 'License key is invalid. Please check the key and try again.', '%TEXTDOMAIN%' )
                : '';

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <Icon icon={ shield } size={ 20 } className="text-primary" />
                    { __( 'Master License Key', '%TEXTDOMAIN%' ) }
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col md:flex-row items-end gap-4">
                    <div className="flex flex-col w-full md:flex-1 gap-1.5">
                        <Label htmlFor="license-key">
                            { __( 'License Key', '%TEXTDOMAIN%' ) }
                        </Label>
                        <Input
                            id="license-key"
                            placeholder="XXXX-XXXX-XXXX-XXXX"
                            value={ key }
                            onChange={ ( e ) => setKey( e.target.value ) }
                        />
                    </div>

                    <div className="flex flex-col w-full md:flex-1 gap-1.5">
                        <Label htmlFor="license-email">
                            { __( 'Registered Email', '%TEXTDOMAIN%' ) }
                        </Label>
                        <Input
                            id="license-email"
                            type="email"
                            placeholder="admin@example.com"
                            value={ email }
                            onChange={ ( e ) => setEmail( e.target.value ) }
                        />
                    </div>

                    <div className="flex gap-2 w-full md:w-auto shrink-0">
                        <Button onClick={ () => onActivate( key, email ) }>
                            { __( 'Activate', '%TEXTDOMAIN%' ) }
                        </Button>
                        <Button variant="outline" onClick={ onDeactivate }>
                            { __( 'Deactivate', '%TEXTDOMAIN%' ) }
                        </Button>
                    </div>
                </div>

                <LicenseStatusMessage type={ statusType } message={ statusMessage } />
            </CardContent>
        </Card>
    );
}
