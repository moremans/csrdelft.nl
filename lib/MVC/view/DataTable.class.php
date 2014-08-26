<?php

/**
 * DataTable.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * DataTables plug-in for jQuery
 * 
 * @see http://www.datatables.net/
 * 
 */
class DataTable implements View {

	private $tableId;
	private $detailSource;
	private $groupByColumn;
	protected $css_classes = array();
	protected $columns = array('name', 'position', 'salary', 'start_date', 'office', 'extn'); // TODO

	public function __construct($tableId, $detailSource = null, $groupByColumn = false, $groupByFixed = false) {
		$this->tableId = $tableId;
		$this->detailSource = $detailSource;
		if ($detailSource) {
			$this->columns[] = '';
		}
		$this->css_classes[] = 'init display';
		if ($groupByColumn === true) {
			$this->css_classes[] = 'groupByColumn';
		} elseif (is_int($groupByColumn)) {
			$this->css_classes[] = 'groupByColumn';
			if ($groupByFixed) {
				$this->css_classes[] = 'groupByFixed';
			}
			$this->groupByColumn = $groupByColumn;
		} else {
			$this->groupByColumn = null;
		}
	}

	public function getModel() {
		return null;
	}

	public function getTitel() {
		return null;
	}

	protected function getTableHead() {
		return null;
	}

	protected function getTableBody() {
		return null;
	}

	protected function getTableFoot() {
		return null;
	}

	private function getConditionalProps() {
		if ($this->groupByColumn) {
			return <<<JS
			, "columnDefs": [
				{
					"visible": false,
					"targets": [{$this->groupByColumn}]
				}
			]
			, "orderFixed": [[{$this->groupByColumn}, "asc"]]
JS;
		}
		return null;
	}

	public function view() {
		if ($this->getTitel()) {
			echo '<h2>' . $this->getTitel() . '</h2>';
		}
		?>
		<div id="<?= $this->tableId ?>_toolbar" class="dataTables_toolbar">
			<button id="rowcount" class="button">Count selected rows</button>
		</div>
		<table id="<?= $this->tableId ?>" class="<?= implode(' ', $this->css_classes) ?>" groupByColumn="<?= $this->groupByColumn ?>">
			<?= $this->getTableHead() ?>
			<?= $this->getTableBody() ?>
			<?= $this->getTableFoot() ?>
		</table>
		<script type="text/javascript">
			$(document).ready(function() {
				var tableId = '<?= $this->tableId ?>';
				var table = '#' + tableId;
				var dataTable = $(table).DataTable({
					"ajax": "/example-data.json",
					"columns": [
						{
							"name": "details",
							"data": null,
							"title": "",
							"type": "string",
							"class": "<?= ($this->detailSource ? 'details-control' : '') ?>",
							"orderable": false,
							"searchable": false,
							"defaultContent": ""
						},
						{
							"name": "name",
							"title": "Name",
							"data": "name",
							"type": "html"
						},
						{
							"name": "position",
							"title": "Position",
							"data": "position",
							"type": "string"
						},
						{
							"name": "office",
							"title": "Office",
							"data": "office",
							"type": "string"
						},
						{
							"name": "salary",
							"title": "Salary",
							"data": "salary",
							"type": "num-fmt"
						},
						{
							"name": "start_date",
							"title": "Start date",
							"data": "start_date",
							"type": "date"
						},
						{
							"name": "extn",
							"title": "Ext.no",
							"data": "extn",
							"type": "num"
						}
					],
					"order": [[1, "asc"]],
					"createdRow": function(row, data, index) {
						$(row).attr('id', tableId + '_' + index); // data array index
						$(row).children(':first').attr('detailSource', '<?= $this->detailSource ?>' + encodeURI(data.name));
					}<?= $this->getConditionalProps() ?>
				});
				// Multiple selection of rows
				$(table + ' tbody').on('click', 'tr', function(event) {
					if (!$(event.target).hasClass('details-control')) {
						fnMultiSelect($(this));
					}
					$(table).trigger('draw.dt', [event, dataTable.settings()]);
				});
				// Opening and closing details
				$(table + ' tbody').on('click', 'td.details-control', function(event) {
					fnChildRow(dataTable, $(this));
				});
				// Group by column
				$(table + '.groupByColumn:not(.groupByFixed)').on('order.dt', fnGroupByColumn);
				$(table + '.groupByColumn').on('draw.dt', fnGroupByColumnDraw);
				$(table + '.groupByColumn').data('expandedGroups', []);
				$(table + '.groupByColumn').data('collapsedGroups', []);
				// Setup toolbar
				$(table).on('draw.dt', function(e, settings) {
					var aantal = $(table + ' tbody tr.selected').length;
					$(table + '_toolbar #rowcount').prop('disabled', aantal < 1);
				});
				$(table + '_toolbar').insertBefore(table);
				$(table + '_toolbar #rowcount').click(function() {
					alert($(table + ' tbody tr.selected').length + ' row(s) selected');
				});
			});
		</script>
		<?php
	}

}
