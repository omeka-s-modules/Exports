# Exports

An [Omeka S](https://omeka.org/s/) module for exporting files.

## For developers

Modules can add custom exporters by registering them as services in their module
configuration under `[exports_module][exporters]`:

```php
'exports_module' => [
    'exporters' => [
        'invokables' => [
            'my_exporter' => \MyModue\Exporter\MyExporter::class,
        ],
    ],
],
```

The MyExporter class must implement `\Exports\Exporter\ExporterInterface`.

```php
class MyExporter implements \Exports\Exporter\ExporterInterface
{
    /**
     * Get the label of this exporter.
     */
    public function getLabel(): string
    {
        return 'My Exporter'; // @translate
    }

    /**
     * Get the description of this exporter.
     */
    public function getDescription(): ?string
    {
        return 'Export text.'; // @translate
    }

    /**
     * Add the form elements used for the export data.
     */
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

    /**
     * Export the data.
     */
    public function export(ExportRepresentation $export, ExportJob $job): void
    {
        file_put_contents(
            sprintf('%s/%s.txt', $job->getExportDirectoryPath(), $export->name()),
            $export->dataValue('text')
        );
    }
}
```


# Copyright

Exports is Copyright © 2020-present Corporation for Digital Scholarship, Vienna,
Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code under
the GNU General Public License, version 3 (GPLv3). The full text of this license
is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.
