import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { ServicioService } from './servicio.service';

@Component({
  selector: 'app-nota',
  templateUrl: './nota.component.html',
  styleUrls: ['./nota.component.scss']
})
export class NotaComponent implements OnInit {

  formularioAddPerson: FormGroup;

  constructor(
    private formBuilder: FormBuilder,
    private servicio: ServicioService
  ) { }

  ngOnInit(): void {
    this.formularioAddPerson = this.formBuilder.group({
      email: ['', Validators.required]
    });

    this.notaFuncion();
    this.removeItem();
  }


  notaFuncion() {
    document.getElementById('inputTitle').addEventListener('focus', () => {
      document.querySelector('.containerField').classList.add('active');
    });

    document.getElementById('inputTitle').addEventListener('focusout', () => {
      document.querySelector('.containerField').classList.remove('active');
    });



    document.getElementById('addPersonButton').addEventListener('click', () => {
      document.getElementById('popupAdd').style.display = 'flex';
    });
  }

  removeItem() {
    let listaItems = document.querySelectorAll('.sharePerson > i');
    listaItems.forEach(element => {
      element.addEventListener('click', (e) => {
        let parentNode = (<HTMLElement>(<HTMLElement>e.target).parentNode);
        parentNode.remove();
      });
    });
  }

  validarDatosAddPerson() {



    if (this.validateEmail(this.formularioAddPerson.value.email)) {
      this.servicio.getUserNote(
        this.formularioAddPerson.value.email,
        JSON.parse(atob(localStorage.getItem('userData'))).email
      ).subscribe(
        (response) => {
          console.log(response);
          if (response.status) {

            let lista = document.querySelectorAll('#peopleContainer > .sharePerson');
            lista.forEach(element => {
              if (element.textContent !== response.data.usuario) {
                document.getElementById('popupAdd').style.display = 'none';
                let contenedor = document.getElementById('peopleContainer');

                let item = document.createElement('label');
                item.classList.add('sharePerson');
                item.appendChild(document.createTextNode(response.data.usuario));
                item.style.cssText = "min-width: fit-content; display: flex; align-items: center; font-size: 14px; margin-right: 10px; border: none; background: #70769C; color: #ffffff; border-radius: 5px; cursor: pointer; outline: 0; padding: 8px 12px;";

                let icon = document.createElement('i');
                icon.classList.add('fas', 'fa-times-circle');
                icon.style.cssText = "color: #565B78; font-size: 17px; margin-left: 8px; transition: all .1s;";
                item.append(icon);

                contenedor.insertBefore(item, contenedor.childNodes[0]);
                this.removeItem();
              } else {
                alert('El usaurio ya está añadido');
                return;
              }
            });

          } else {
            alert(response.response);
          }
        },
        (error) => {
          console.log(error);
        });
    } else {
      console.log('Email incorrecto');
    }

  }

  validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }
}
