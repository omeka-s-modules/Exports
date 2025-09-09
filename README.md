# Exports

An [Omeka S](https://omeka.org/s/) module for exporting assets.

## Installation

Follow these [instructions](https://omeka.org/s/docs/user-manual/modules/) on how
to install the module. On the module configuration page you must set an "Exports
directory path". This is the path to the directory where your exports will be built.
The path must exist and be writable by the web server.

## Export

To export assets, click on the "Exports" link in the side navigation, click on the
"Create an export" button, select what you would like to export, configure export
options, and initiate the export job by clicking on the "Begin export" button. You
can check on the status of the export job by refreshing the page or in the "Details"
sidebar.

Once the export job is complete, all exported assets will be available for download
within one ZIP file. The download link is available by clicking on the "Download"
icon in the export row, or in the "Details" sidebar.

This module comes with two exporters:

- Resources CSV: Export a [CSV](https://en.wikipedia.org/wiki/Comma-separated_values) file containing data about selected resources
- Resources JSON-LD: Export a [JSON-LD](https://en.wikipedia.org/wiki/JSON-LD) file containing data about selected resources

The exports browse page contains paginated data about all exports. Each row contains
icons that do certain things when clicked:

- "Details": View the details sidebar, which contains detailed information about the export
- "Re-export": Open the export form, populated with the specific configuration options
- "Download": Download the export ZIP file, available once the export job is complete
- "Delete": Delete the export resouce, the export ZIP file, and any leftover server artifacts

## For developers

### Adding custom exporters

Modules can add custom exporters by registering them as services in module configuration
under `[exports_module][exporters]`. For example, if you want to register a "MyExporter"
exporter for your "MyModule" module:

```php
'exports_module' => [
    'exporters' => [
        'invokables' => [
            'my_module_exporter' => \MyModule\Exporter\MyExporter::class,
        ],
    ],
],
```

The MyExporter class must implement `\Exports\Exporter\ExporterInterface`:

```php
namespace MyModule\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Exporter\ExporterInterface;
use Exports\Job\ExportJob;
use Laminas\Form\Fieldset;


class MyExporter implements ExporterInterface
{
    public function getLabel(): string
    {
        return 'My Module Exporter'; // @translate
    }
    public function getDescription(): ?string
    {
        return 'Export text.'; // @translate
    }
    public function addElements(Fieldset $fieldset): void
    {
        $fieldset->add([
            'type' => 'textarea',
            'name' => 'text',
            'options' => [
                'label' => 'Text', // @translate
            ],
        ]);
    }
    public function export(ExportRepresentation $export, ExportJob $job): void
    {
        file_put_contents(
            sprintf('%s/%s.txt', $job->getExportDirectoryPath(), $export->name()),
            $export->dataValue('text')
        );
    }
}
```

`ExporterInterface::export()` is where you do the actual export. Prior to calling
this method, the export job will create a temporary directory where you should place
all exported files and directories. Use `$job->getExportDirectoryPath()` to get
the path to this directory. After work is done, the export job will create the export
ZIP file, copy the file to Omeka storage, and delete any leftover server artifacts.

### Extending the ResourcesCsv exporter

The ResourcesCsv exporter allows modules to add columns to the CSV file using the
`exports.resources_csv.get_field_data` event. For example, in `Module::attachListeners()`:

```php
$sharedEventManager->attach(
    '*',
    'exports.resources_csv.get_field_data',
    function (Event $event) {
        $params = $event->getParams();

        $k = $params['k'];
        $v = $params['v'];
        $export = $params['export'];
        $fieldData = $params['field_data'];

        if ('o-module-mymodule:mydata' === $k) {
            // Do any processing to the value and add the header-field pair to
            // the $fieldData ArrayObject.
            $fieldData[] = [$k, $v];
        }
    }
);
```

The event provides the following parameters:

- `k`: The JSON-LD key; use to detect relevant values
- `v`: The JSON-LD value
- `export`: The export representation; use to get export data if needed
- `field_data`: An `ArrayObject`; use to set CSV header-field pair(s)

# Copyright

Exports is Copyright © 2020-present Corporation for Digital Scholarship, Vienna,
Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code under
the GNU General Public License, version 3 (GPLv3). The full text of this license
is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.
