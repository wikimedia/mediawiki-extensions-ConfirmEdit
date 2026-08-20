ConfirmEdit
=========

ConfirmEdit provides a module that allows frontend JavaScript interfaces to display a CAPTCHA challenge to users.

## Using the widget

The widget is defined in the `ext.confirmEdit.CaptchaWidget` ResourceLoader module. The construction of the class
gives you an interface to manage the CAPTCHA challenge and its state.

### Show a CAPTCHA after a failed API request

To render the CAPTCHA to the user after a failed API request, you would:
* Load the `ext.confirmEdit.CaptchaWidget` module
* Construct an instance of the `CaptchaWidget` class, passing in the container where the CAPTCHA should be rendered and the name of your interface for analytics purposes
* Call `updateForFailure()` with the CAPTCHA data returned in the API response to provide the CAPTCHA challenge to the widget
* Call `renderCaptcha()` to render the CAPTCHA challenge in the provided container

When your user submits the form, you can call `getCaptchaDataForSubmission()` to get the CAPTCHA data to submit with your API request.

#### Code examples

To render the CAPTCHA after a failed API request, you can use the following code:

```js
const captchaData = returnedData.yourAction.captcha;
mw.loader.using( 'ext.confirmEdit.CaptchaWidget' ).then( () => {
    const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
        // A selector matching one element or an HTMLElement, where the CAPTCHA will be shown
        container: '#captcha-container',
        // The name of your interface for analytics purposes
        interfaceName: 'my-interface',
    } );

    // Render the CAPTCHA using the provided CAPTCHA data from the failed API request
    captchaWidget.updateForFailure( captchaData ).then( () => {
      captchaWidget.renderCaptcha();
    } ).catch( ( error ) => {
      // Handle the error by displaying it to the user
    } );
} );
```

To get the CAPTCHA data to submit with your API request, you can use the following code:

```js
const apiDataToSubmit = { action: 'edit' };
captchaWidget.getCaptchaDataForSubmission().then( ( captchaData ) => {
    Object.assign( apiDataToSubmit, captchaData );
} ).catch( ( error ) => {
    // Handle the error by displaying it to the user
} );
```

### Show a CAPTCHA before the first edit attempt

If your wiki uses hCaptcha for editing, then you can choose to display a CAPTCHA challenge to the user before they make their first edit attempt.

To know if a user needs to solve a CAPTCHA before the first edit API attempt, you can call `mw.libs.confirmEdit.CaptchaWidget.static.captchaNeededForEdit()`. If it returns `hcaptcha`, then you can render a CAPTCHA without calling `updateForFailure()` first.

Note that the API request may still fail for a CAPTCHA challenge if AbuseFilter is installed and the user triggers an AbuseFilter that requires a CAPTCHA challenge. In that case, you would call `updateForFailure()` with the CAPTCHA data returned in the API response to provide the stricter challenge to the widget.

#### Code examples

To render the CAPTCHA before the first edit attempt, you can use the following code:

```js
mw.loader.using( 'ext.confirmEdit.CaptchaWidget' ).then( ( require ) => {
    if ( mw.libs.confirmEdit.CaptchaWidget.static.captchaNeededForEdit() === 'hcaptcha' ) {
        const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
            // A selector matching one element or an HTMLElement, where the CAPTCHA will be shown
            container: '#captcha-container',
            // The name of your interface for analytics purposes
            interfaceName: 'my-interface',
            type: 'hcaptcha',
        } );

        captchaWidget.renderCaptcha().catch( ( error ) => {
          // Handle the error by displaying it to the user
        } );
    }
} );
```

## Examples

The following extensions use the `ext.confirmEdit.CaptchaWidget` module to display a CAPTCHA challenge to users. These may be useful examples to see how the widget is integrated into a frontend interface:
* [DiscussionTools](https://www.mediawiki.org/wiki/Extension:DiscussionTools)
* [UploadWizard](https://www.mediawiki.org/wiki/Extension:UploadWizard)
