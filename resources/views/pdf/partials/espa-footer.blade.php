<style>
    footer.espa-footer { position: fixed; right: 0; bottom: -88px; left: 0; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
    footer.espa-footer .line { border-top: 1px solid #777; padding-top: 3px; }
    footer.espa-footer table { width: 100%; border-collapse: collapse; }
    footer.espa-footer td { text-align: center; vertical-align: middle; }
    footer.espa-footer .espa-logo { width: 310px; }
    footer.espa-footer .dqs-logo { width: 52px; opacity: .5; }
</style>
<footer class="espa-footer">
    <div class="line"><strong>Ημερομηνία Εκτύπωσης</strong> {{ now()->format('d/m/Y') }}</div>
    <table>
        <tr>
            <td style="width:70%"><img class="espa-logo" src="{{ public_path('images/espa-logo-2021-2027.png') }}" alt="ΕΣΠΑ 2021-2027"></td>
            <td style="width:30%"><img class="dqs-logo" src="{{ public_path('images/dqs.png') }}" alt="DQS"></td>
        </tr>
    </table>
</footer>
