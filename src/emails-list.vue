<template>
	<div id="wpum-emails-list">
		<h1>WPUM Emails Customization</h1>

		<div class="notice notice-success is-dismissible" v-if="success">
			<p><strong>Test email successfully sent.</strong></p>
		</div>

		<div class="wp-filter">
			<form action="" class="search-form search-plugins">
				<label>
					<input name="email" value="" class="wp-filter-search" placeholder="Search plugins..." type="text">
				</label>
				<a href="#" class="button"><span class="dashicons dashicons-email-alt"></span> Send test mail</a>
				<div class="spinner is-active" v-if="loading"></div>
			</form>
		</div>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="icon-col"></th>
					<th scope="col" class="column-primary">Email</th>
					<th scope="col">Description</th>
					<th scope="col">Recipient(s)</th>
					<th scope="col"></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<div data-balloon="I'm a tooltip." data-balloon-pos="right">
							<span class="dashicons dashicons-yes"></span>
						</div>
					</td>
					<td> <a href="#">New account</a></td>
					<td>Description here</td>
					<td>email@email.com</td>
					<td><a href="#" class="button"><span class="dashicons dashicons-edit"></span> Customize</a></td>
				</tr>
				<tr>
					<td><span class="dashicons dashicons-yes"></span></td>
					<td> <a href="#">New account</a></td>
					<td>Description here</td>
					<td>email@email.com</td>
					<td><a href="#" class="button"><span class="dashicons dashicons-edit"></span> Customize</a></td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col"></th>
					<th scope="col">Email</th>
					<th scope="col">Description</th>
					<th scope="col">Recipient(s)</th>
					<th scope="col"></th>
				</tr>
			</tfoot>
		</table>
  	</div>
</template>

<script>
export default {
	name: 'emails-editor',
	data() {
		return {
			loading: false,
			success: false
		}
	}
}
</script>

<style lang="scss">
@import url('https://cdnjs.cloudflare.com/ajax/libs/balloon-css/0.5.0/balloon.min.css');
#wpum-emails-list table {
	margin-top: 1em;

	.icon-col {
		width: 20px;
	}

	a:not(.button) {
		font-weight: bold;
	}

	a.button {
		span {
			margin-top: 3px;
			margin-right: 3px;
		}
	}

	td {
		vertical-align: middle;
	}

	.dashicons-yes {
		background: #00be28;
		color: #fff;
		border-radius: 9999px;
		&:before {
			font-size: 18px;
		}
	}

}

#wpum-emails-list {
	.wp-filter {
		input[type="text"] {
			margin: 0;
			padding: 3px 5px;
			width: 280px;
			max-width: 100%;
			font-size: 16px;
			font-weight: 300;
			line-height: 1.5;
		}
		a.button {
			margin-top: 2px;
			margin-left: 3px;
			span {
				display: inline-block;
				margin-top: 3px;
				padding-right: 3px;
			}
		}
		.search-form {
			float: none;
		}
	}
	.spinner {
		float: none;
		margin-top: -1px;
	}
}
</style>
